<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Venta;
use App\Models\Pago;

echo "=== DIAGNÓSTICO VENTA-PAGO ===\n\n";

echo "📊 VENTAS:\n";
$ventas = Venta::with(['pagos', 'cliente'])->get();

foreach ($ventas as $venta) {
    echo "ID: {$venta->id}\n";
    echo "Estado: {$venta->estado}\n";
    echo "Total: \${$venta->total}\n";
    echo "Cliente: " . ($venta->cliente->nombre ?? 'N/A') . "\n";
    echo "Fecha: {$venta->created_at}\n";
    echo "Pagos asociados: {$venta->pagos->count()}\n";
    
    foreach ($venta->pagos as $pago) {
        echo "  - Pago ID: {$pago->id}\n";
        echo "    Estado: {$pago->estado}\n";
        echo "    Monto: \${$pago->monto}\n";
        echo "    Wompi Transaction ID: {$pago->wompi_transaction_id}\n";
        echo "    Wompi Reference: {$pago->wompi_reference}\n";
        echo "    Creado: {$pago->created_at}\n";
    }
    echo "\n" . str_repeat('-', 50) . "\n\n";
}

echo "💾 TODOS LOS PAGOS:\n";
$pagos = Pago::all();
foreach ($pagos as $pago) {
    echo "ID: {$pago->id} | Estado: {$pago->estado} | Venta ID: {$pago->venta_id} | Wompi ID: {$pago->wompi_transaction_id}\n";
}