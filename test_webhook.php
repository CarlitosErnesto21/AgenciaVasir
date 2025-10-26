<?php

require_once 'vendor/autoload.php';

use App\Models\Pago;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;

// Simular un payload de webhook de Wompi
$webhookPayload = [
    'event' => 'transaction.updated',
    'data' => [
        'transaction' => [
            'id' => 'transaction-id-test',
            'reference' => null, // Probaremos con reference vacío primero
            'amount_in_cents' => 1200,
            'status' => 'APPROVED',
            'payment_method' => [
                'type' => 'CARD'
            ]
        ]
    ],
    'timestamp' => time(),
    'signature' => [
        'properties' => ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'],
        'checksum' => 'test-checksum'
    ]
];

echo "=== PRUEBA WEBHOOK WOMPI ===\n";
echo "Payload simulado:\n";
echo json_encode($webhookPayload, JSON_PRETTY_PRINT) . "\n\n";

// Buscar pago existente en la base de datos
$pagos = Pago::where('estado', 'pending')->get();
echo "Pagos pendientes encontrados: " . $pagos->count() . "\n";

foreach ($pagos as $pago) {
    echo "- Pago ID: {$pago->id}, Monto: {$pago->monto}, Referencia: {$pago->wompi_reference}\n";
}

// Probar búsqueda por transaction_id
$transactionId = 'transaction-id-test';
$pagoByTransaction = Pago::where('wompi_transaction_id', $transactionId)->orWhere('wompi_reference', $transactionId)->first();
echo "\nBúsqueda por transaction_id '{$transactionId}': " . ($pagoByTransaction ? 'ENCONTRADO' : 'NO ENCONTRADO') . "\n";

// Ahora probar con el ID real del primer pago
if ($pagos->count() > 0) {
    $primerPago = $pagos->first();
    echo "\n=== ACTUALIZANDO PRIMER PAGO ===\n";
    echo "Pago ID: {$primerPago->id}\n";
    echo "Estado actual: {$primerPago->estado}\n";
    
    // Simular actualización del webhook
    $primerPago->wompi_transaction_id = $transactionId;
    $primerPago->wompi_reference = 'REF-TEST-001';
    $primerPago->estado = 'completed';
    $primerPago->save();
    
    echo "Pago actualizado exitosamente!\n";
    
    // Actualizar venta asociada
    if ($primerPago->venta_id) {
        $venta = Venta::find($primerPago->venta_id);
        if ($venta) {
            $venta->estado = 'completada';
            $venta->save();
            echo "Venta ID {$venta->id} actualizada a 'completada'\n";
        }
    }
    
    echo "\n=== VERIFICACIÓN FINAL ===\n";
    $pagoActualizado = Pago::find($primerPago->id);
    $ventaActualizada = $venta ? Venta::find($venta->id) : null;
    
    echo "Pago estado: {$pagoActualizado->estado}\n";
    echo "Venta estado: " . ($ventaActualizada ? $ventaActualizada->estado : 'N/A') . "\n";
}

echo "\n=== PRUEBA COMPLETADA ===\n";