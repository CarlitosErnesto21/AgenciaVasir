<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pago;
use App\Models\Venta;

class TestWebhook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:webhook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simular webhook de Wompi para actualizar pagos pendientes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== PRUEBA WEBHOOK WOMPI ===');

        // Buscar pagos pendientes
        $pagos = Pago::where('estado', 'pending')->get();
        $this->info("Pagos pendientes encontrados: " . $pagos->count());

        foreach ($pagos as $pago) {
            $this->line("- Pago ID: {$pago->id}, Monto: {$pago->monto}, Referencia: {$pago->wompi_reference}");
        }

        if ($pagos->count() > 0) {
            $primerPago = $pagos->first();

            $this->info("\n=== ACTUALIZANDO PRIMER PAGO ===");
            $this->line("Pago ID: {$primerPago->id}");
            $this->line("Estado actual: {$primerPago->estado}");

            // Simular actualización del webhook
            $primerPago->wompi_transaction_id = 'transaction-id-test-' . time();
            $primerPago->wompi_reference = 'REF-TEST-' . $primerPago->id;
            $primerPago->estado = 'completed';
            $primerPago->save();

            $this->info("Pago actualizado exitosamente!");

            // Actualizar venta asociada
            if ($primerPago->venta_id) {
                $venta = Venta::find($primerPago->venta_id);
                if ($venta) {
                    $venta->estado = 'completada';
                    $venta->save();
                    $this->info("Venta ID {$venta->id} actualizada a 'completada'");
                }
            }

            $this->info("\n=== VERIFICACIÓN FINAL ===");
            $pagoActualizado = Pago::find($primerPago->id);
            $ventaActualizada = $venta ?? null;

            $this->line("Pago estado: {$pagoActualizado->estado}");
            $this->line("Venta estado: " . ($ventaActualizada ? $ventaActualizada->estado : 'N/A'));
        } else {
            $this->warn("No hay pagos pendientes para probar");
        }

        $this->info("\n=== PRUEBA COMPLETADA ===");

        return Command::SUCCESS;
    }
}
