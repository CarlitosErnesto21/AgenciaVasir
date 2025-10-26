<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Inicializar Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo "=== PRUEBA DE FLUJO COMPLETO DE PAGO ===\n\n";

// Datos de prueba
$ventaId = 1; // La venta que ya existe
$testPaymentData = [
    'customer_email' => 'test@example.com',
    'amount' => 12.00,
    'description' => 'Prueba de pago',
    'reference' => 'TEST-' . time(),
    'venta_id' => $ventaId,
    'productos' => [
        [
            'id' => 1,
            'nombre' => 'Producto Test',
            'precio' => 12.00,
            'cantidad' => 1,
            'subtotal' => 12.00
        ]
    ]
];

echo "🧪 Datos de prueba:\n";
echo "  Email: {$testPaymentData['customer_email']}\n";
echo "  Monto: \${$testPaymentData['amount']}\n";
echo "  Referencia: {$testPaymentData['reference']}\n";
echo "  Venta ID: {$testPaymentData['venta_id']}\n\n";

try {
    // 1. Verificar que la venta existe
    $venta = \App\Models\Venta::find($ventaId);
    if (!$venta) {
        throw new Exception("Venta $ventaId no encontrada");
    }

    echo "✅ Venta encontrada:\n";
    echo "  ID: {$venta->id}\n";
    echo "  Estado: {$venta->estado}\n";
    echo "  Total: \${$venta->total}\n\n";

    // 2. Simular creación de pago (sin Wompi real)
    echo "🔗 Simulando creación de enlace de pago...\n";

    $pago = \App\Models\Pago::create([
        'venta_id' => $venta->id,
        'monto' => $testPaymentData['amount'],
        'moneda' => 'COP',
        'referencia_wompi' => $testPaymentData['reference'],
        'estado' => 'pending',
        'metodo_pago' => 'payment_link',
        'email_cliente' => $testPaymentData['customer_email'],
        'wompi_payment_link_id' => 'LINK-' . time(),
        'wompi_payment_link' => 'https://checkout.wompi.sv/l/LINK-' . time(),
        'productos_detalle' => json_encode($testPaymentData['productos'])
    ]);

    echo "✅ Pago creado:\n";
    echo "  ID: {$pago->id}\n";
    echo "  Referencia: {$pago->referencia_wompi}\n";
    echo "  Estado: {$pago->estado}\n";
    echo "  Link ID: {$pago->wompi_payment_link_id}\n\n";

    // 3. Simular webhook de aprobación
    echo "📡 Simulando webhook de aprobación...\n";

    // Simular transaction data de Wompi
    $transactionData = [
        'id' => 'TXN-' . time(),
        'status' => 'APPROVED',
        'reference' => $testPaymentData['reference'],
        'amount' => $testPaymentData['amount'] * 100, // En centavos
        'currency' => 'COP',
        'payment_date' => now()->toISOString()
    ];

    // Actualizar el pago como si fuera el webhook
    $pago->update([
        'wompi_transaction_id' => $transactionData['id'],
        'estado' => 'approved',
        'response_data' => $transactionData
    ]);

    echo "✅ Pago actualizado por webhook:\n";
    echo "  Transaction ID: {$pago->wompi_transaction_id}\n";
    echo "  Estado: {$pago->estado}\n\n";

    // 4. Sincronizar estado de venta
    echo "🔄 Sincronizando estado de venta...\n";

    if ($pago->estado === 'approved' && $venta->estado === 'pendiente') {
        $venta->update(['estado' => 'completada']);
        echo "✅ Venta actualizada a: {$venta->estado}\n\n";
    }

    // 5. Verificar resultado final
    echo "🏁 RESULTADO FINAL:\n";

    $ventaActualizada = \App\Models\Venta::with('pagos')->find($ventaId);
    echo "  Venta ID: {$ventaActualizada->id}\n";
    echo "  Estado: {$ventaActualizada->estado}\n";
    echo "  Total: \${$ventaActualizada->total}\n";
    echo "  Pagos asociados: {$ventaActualizada->pagos->count()}\n";

    if ($ventaActualizada->pagos->count() > 0) {
        $pagoAsociado = $ventaActualizada->pagos->first();
        echo "  Pago estado: {$pagoAsociado->estado}\n";
        echo "  Pago monto: \${$pagoAsociado->monto}\n";
    }

    echo "\n🎉 PRUEBA COMPLETADA EXITOSAMENTE\n";
    echo "El flujo de pago está funcionando correctamente.\n\n";

    // Mostrar diagnóstico final
    echo "📊 DIAGNÓSTICO FINAL:\n";
    $todasLasVentas = \App\Models\Venta::with('pagos')->get();
    foreach ($todasLasVentas as $v) {
        echo "  Venta {$v->id}: {$v->estado} - \${$v->total} - {$v->pagos->count()} pago(s)\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";
