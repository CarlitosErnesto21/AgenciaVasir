<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockReservation;
use Illuminate\Support\Facades\Log;

class CleanExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:clean-expired 
                           {--dry-run : Solo mostrar lo que se haría sin ejecutar}
                           {--force : Forzar limpieza sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpiar reservas de stock expiradas automáticamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Iniciando limpieza de reservas expiradas...');
        $this->newLine();

        // Mostrar estadísticas actuales
        $stats = StockReservation::obtenerEstadisticas();
        $this->info("📊 Estadísticas actuales:");
        $this->table(
            ['Estado', 'Cantidad', 'Valor Total'],
            [
                ['Activas', $stats['activas'], '$' . number_format($stats['valor_total_reservado'], 2)],
                ['Expiradas', $stats['expiradas'], '-'],
                ['Confirmadas', $stats['confirmadas'], '-'],
                ['Canceladas', $stats['canceladas'], '-'],
            ]
        );

        // Buscar reservas para expirar
        $reservasParaExpirar = StockReservation::where('estado', 'activa')
                                             ->where('expira_en', '<=', now())
                                             ->with('producto')
                                             ->get();

        if ($reservasParaExpirar->isEmpty()) {
            $this->info('✅ No hay reservas expiradas para limpiar.');
            return 0;
        }

        $this->warn("⚠️  Se encontraron {$reservasParaExpirar->count()} reservas expiradas:");
        
        // Mostrar detalles de las reservas a expirar
        if ($reservasParaExpirar->count() <= 20) {
            $this->table(
                ['ID', 'Producto', 'Cantidad', 'Referencia', 'Expiró hace'],
                $reservasParaExpirar->map(function ($reserva) {
                    return [
                        $reserva->id,
                        $reserva->producto->nombre ?? "Producto #{$reserva->producto_id}",
                        $reserva->cantidad_reservada,
                        $reserva->referencia_wompi,
                        $reserva->expira_en->diffForHumans()
                    ];
                })->toArray()
            );
        } else {
            $this->info("Demasiadas reservas para mostrar individualmente ({$reservasParaExpirar->count()})");
        }

        // Dry run check
        if ($this->option('dry-run')) {
            $this->warn('🔍 Modo DRY-RUN: No se realizarán cambios.');
            $this->info('Las reservas mostradas arriba serían expiradas.');
            return 0;
        }

        // Confirmación
        if (!$this->option('force')) {
            if (!$this->confirm("¿Proceder con la limpieza de {$reservasParaExpirar->count()} reservas expiradas?")) {
                $this->info('Operación cancelada por el usuario.');
                return 0;
            }
        }

        // Ejecutar limpieza
        $this->info('🔄 Procesando limpieza...');
        
        try {
            $totalExpiradas = StockReservation::limpiarReservasExpiradas();
            
            $this->info("✅ Limpieza completada exitosamente!");
            $this->info("📊 Reservas expiradas: {$totalExpiradas}");
            
            // Mostrar nuevas estadísticas
            $this->newLine();
            $newStats = StockReservation::obtenerEstadisticas();
            $this->info("📊 Estadísticas después de la limpieza:");
            $this->table(
                ['Estado', 'Cantidad', 'Valor Total'],
                [
                    ['Activas', $newStats['activas'], '$' . number_format($newStats['valor_total_reservado'], 2)],
                    ['Expiradas', $newStats['expiradas'], '-'],
                    ['Confirmadas', $newStats['confirmadas'], '-'],
                    ['Canceladas', $newStats['canceladas'], '-'],
                ]
            );

            Log::info('Limpieza manual de reservas expiradas completada', [
                'total_expiradas' => $totalExpiradas,
                'ejecutado_por' => 'comando_manual'
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Error durante la limpieza: " . $e->getMessage());
            Log::error('Error en limpieza manual de reservas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}