<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pago;
use App\Models\StockReservation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestPaymentConfirmation extends Command
{
    protected $signature = 'test:payment-confirmation {referencia} {status=approved}';
    protected $description = 'Probar confirmación de pago directamente (sin webhook)';

    public function handle()
    {
        $referencia = $this->argument('referencia');
        $status = $this->argument('status');

        $this->info("🧪 PRUEBA DE CONFIRMACIÓN DE PAGO");
        $this->info("Referencia: {$referencia}");
        $this->info("Estado: {$status}");
        $this->newLine();

        // Buscar pago
        $pago = Pago::where('referencia_wompi', $referencia)->first();

        if (!$pago) {
            $this->error("❌ No se encontró pago con referencia: {$referencia}");
            return 1;
        }

        $this->info("🔍 Pago encontrado:");
        $this->info("   ID: {$pago->id}");
        $this->info("   Estado actual: {$pago->estado}");
        $this->info("   Venta ID: {$pago->venta_id}");
        $this->newLine();

        try {
            DB::beginTransaction();

            // Actualizar estado del pago
            $oldStatus = $pago->estado;
            $pago->update([
                'estado' => $status,
                'response_data' => [
                    'simulated' => true,
                    'test_date' => now()->toISOString(),
                    'original_status' => $oldStatus
                ]
            ]);

            $this->info("💳 Pago actualizado:");
            $this->info("   Estado anterior: {$oldStatus}");
            $this->info("   Estado nuevo: {$status}");

            if ($status === 'approved') {
                // ✅ PAGO APROBADO - Confirmar reservas
                $reservasConfirmadas = StockReservation::confirmarReservasPorReferencia($referencia);

                $this->info("🎉 Reservas confirmadas: {$reservasConfirmadas->count()}");

                // Actualizar venta si existe
                if ($pago->venta_id) {
                    $venta = $pago->venta;
                    if ($venta->estado === 'pendiente') {
                        $venta->update(['estado' => 'completada']);

                        $this->info("✅ Venta actualizada:");
                        $this->info("   ID: {$venta->id}");
                        $this->info("   Estado: pendiente → completada");

                        // Crear movimientos de inventario
                        foreach ($venta->detalleVentas as $detalle) {
                            \App\Models\Inventario::create([
                                'fecha_movimiento' => now(),
                                'cantidad' => $detalle->cantidad,
                                'tipo_movimiento' => 'SALIDA',
                                'motivo' => 'venta_confirmada_test',
                                'observacion' => "Venta #{$venta->id} confirmada por test - {$detalle->producto->nombre}",
                                'user_id' => 1,
                                'producto_id' => $detalle->producto_id,
                                'venta_id' => $venta->id
                            ]);
                        }

                        $this->info("📦 Movimientos de inventario creados");
                    }
                }

            } elseif (in_array($status, ['declined', 'failed', 'error', 'voided'])) {
                // ❌ PAGO RECHAZADO - Cancelar reservas
                $reservasCanceladas = StockReservation::cancelarReservasPorReferencia(
                    $referencia,
                    "Pago {$status} - Test manual"
                );

                $this->info("❌ Reservas canceladas: {$reservasCanceladas->count()}");

                // Cancelar venta si existe
                if ($pago->venta_id) {
                    $venta = $pago->venta;
                    if ($venta->estado === 'pendiente') {
                        $venta->update(['estado' => 'cancelada']);

                        $this->info("❌ Venta cancelada:");
                        $this->info("   ID: {$venta->id}");
                        $this->info("   Estado: pendiente → cancelada");
                    }
                }
            }

            DB::commit();

            $this->newLine();
            $this->info("✅ Prueba completada exitosamente");

            // Mostrar estadísticas finales
            $stats = StockReservation::obtenerEstadisticas();
            $this->info("📊 Estadísticas de reservas:");
            $this->table(
                ['Estado', 'Cantidad'],
                [
                    ['Activas', $stats['activas']],
                    ['Confirmadas', $stats['confirmadas']],
                    ['Canceladas', $stats['canceladas']],
                    ['Expiradas', $stats['expiradas']]
                ]
            );

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("Trace: " . $e->getTraceAsString());
            return 1;
        }
    }
}
