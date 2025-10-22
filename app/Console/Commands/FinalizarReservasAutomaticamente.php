<?php

namespace App\Console\Commands;

use App\Http\Controllers\ReservaController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FinalizarReservasAutomaticamente extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservas:finalizar-automaticamente 
                            {--dry-run : Ejecutar en modo de prueba sin hacer cambios}
                            {--force : Forzar la finalización incluso si hay advertencias}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finaliza automáticamente las reservas cuyas fechas de tour han pasado';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Iniciando proceso de finalización automática de reservas...');
        
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        if ($dryRun) {
            $this->warn('⚠️ Ejecutando en modo DRY-RUN - No se realizarán cambios');
        }
        
        try {
            // Crear una instancia del controlador
            $controller = new ReservaController();
            
            // Llamar al método de finalización automática
            $response = $controller->finalizarAutomaticamente();
            $data = $response->getData(true);
            
            if ($response->getStatusCode() === 200) {
                $this->info("✅ Proceso completado exitosamente");
                
                if (isset($data['reservas_procesadas'])) {
                    $this->info("📊 Reservas procesadas: {$data['reservas_procesadas']}");
                }
                
                if (isset($data['reservas_finalizadas'])) {
                    $this->info("🎯 Reservas finalizadas: {$data['reservas_finalizadas']}");
                }
                
                if (isset($data['detalles']) && count($data['detalles']) > 0) {
                    $this->info("\n📋 Detalles de reservas finalizadas:");
                    
                    foreach ($data['detalles'] as $detalle) {
                        $this->line("  • Reserva #{$detalle['id']} - Cliente: {$detalle['cliente']} - Tour: {$detalle['tour']}");
                    }
                } else {
                    $this->info("ℹ️ No se finalizaron reservas en esta ejecución");
                }
                
                if ($dryRun && isset($data['reservas_finalizadas']) && $data['reservas_finalizadas'] > 0) {
                    $this->warn("\n⚠️ Esto fue una simulación. Para ejecutar los cambios reales, ejecute el comando sin --dry-run");
                }
                
                Log::info('Comando de finalización automática ejecutado', [
                    'reservas_procesadas' => $data['reservas_procesadas'] ?? 0,
                    'reservas_finalizadas' => $data['reservas_finalizadas'] ?? 0,
                    'dry_run' => $dryRun
                ]);
                
            } else {
                $message = isset($data['message']) ? $data['message'] : 'Error desconocido';
                $this->error("❌ Error en el proceso: {$message}");
                Log::error('Error en comando de finalización automática', ['response' => $data]);
                return Command::FAILURE;
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Error inesperado: {$e->getMessage()}");
            Log::error('Excepción en comando de finalización automática', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}