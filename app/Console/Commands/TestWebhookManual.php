<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\PagoController;
use App\Models\Pago;
use Illuminate\Support\Facades\Log;

class TestWebhookManual extends Command
{
    protected $signature = 'test:webhook-manual {referencia}';
    protected $description = 'Simula un webhook de Wompi para probar la actualización manual';

    public function handle()
    {
        $referencia = $this->argument('referencia');

        // Buscar el pago por referencia
        $pago = Pago::where('referencia_wompi', $referencia)->first();

        if (!$pago) {
            $this->error("❌ No se encontró un pago con referencia: {$referencia}");
            return 1;
        }

        $this->info("🔍 Pago encontrado:");
        $this->info("   ID: {$pago->id}");
        $this->info("   Referencia: {$pago->referencia_wompi}");
        $this->info("   Estado actual: {$pago->estado}");
        $this->info("   Venta ID: {$pago->venta_id}");

        // Simular webhook payload
        $webhookData = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => 'T-' . time(), // ID simulado
                    'reference' => $referencia,
                    'status' => 'APPROVED',
                    'amount_in_cents' => (int)($pago->monto * 100),
                    'currency' => 'COP',
                    'payment_method' => [
                        'type' => 'CARD',
                        'extra' => [
                            'name' => 'VISA-0001',
                            'last_four' => '0001'
                        ]
                    ],
                    'created_at' => now()->toISOString(),
                    'finalized_at' => now()->toISOString()
                ]
            ],
            'sent_at' => now()->toISOString()
        ];

        $this->info("\n🚀 Simulando webhook...");

        // Crear request simulado
        $request = new Request();
        $request->merge($webhookData);
        $request->headers->set('X-Event', 'transaction.updated');

        // Ejecutar webhook
        $controller = new PagoController();

        try {
            $response = $controller->webhook($request);

            $this->info("✅ Webhook ejecutado");
            $this->info("   Status: " . $response->getStatusCode());

            // Verificar cambios
            $pagoActualizado = Pago::find($pago->id);
            $this->info("\n📊 Estado después del webhook:");
            $this->info("   Pago estado: {$pagoActualizado->estado}");

            if ($pagoActualizado->venta_id) {
                $venta = $pagoActualizado->venta;
                $this->info("   Venta estado: {$venta->estado}");
            }

            if ($pagoActualizado->estado === 'completado') {
                $this->info("🎉 ¡Webhook procesado correctamente!");
            } else {
                $this->warn("⚠️  El pago no se marcó como completado");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error ejecutando webhook: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
