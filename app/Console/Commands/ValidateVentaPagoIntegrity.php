<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Venta;
use App\Models\Pago;

class ValidateVentaPagoIntegrity extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'ventas:validate-integrity
                           {--fix : Intentar corregir inconsistencias automáticamente}
                           {--report : Generar reporte detallado}';

    /**
     * The console command description.
     */
    protected $description = 'Validar integridad entre ventas y pagos del sistema Wompi';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Iniciando validación de integridad Venta-Pago...');
        $this->line('');

        $startTime = microtime(true);

        // Estadísticas generales
        $totalVentas = Venta::count();
        $totalPagos = Pago::count();

        $this->info("📊 Estadísticas Generales:");
        $this->line("   Total Ventas: {$totalVentas}");
        $this->line("   Total Pagos: {$totalPagos}");
        $this->line('');

        // Validar inconsistencias
        $inconsistencias = $this->detectarInconsistencias();
        $totalInconsistencias = count($inconsistencias);

        if ($totalInconsistencias === 0) {
            $this->info('✅ Sistema íntegro: No se encontraron inconsistencias');
        } else {
            $this->warn("⚠️  Se encontraron {$totalInconsistencias} inconsistencias:");
            $this->line('');

            foreach ($inconsistencias as $inconsistencia) {
                $this->displayInconsistencia($inconsistencia);
            }

            // Opción de corrección automática
            if ($this->option('fix')) {
                $this->intentarCorreciones($inconsistencias);
            }
        }

        // Generar reporte si se solicita
        if ($this->option('report')) {
            $this->generarReporte($inconsistencias);
        }

        $processingTime = round((microtime(true) - $startTime) * 1000, 2);
        $this->line('');
        $this->info("⏱️  Validación completada en {$processingTime}ms");

        return $totalInconsistencias === 0 ? 0 : 1;
    }

    /**
     * Detectar inconsistencias en el sistema
     */
    private function detectarInconsistencias(): array
    {
        $inconsistencias = [];

        $this->info('🔍 Detectando inconsistencias...');

        // 1. Ventas completadas sin pago aprobado
        $ventasCompletadasSinPago = Venta::where('estado', 'completada')
            ->whereDoesntHave('pagos', function($query) {
                $query->where('estado', 'approved');
            })->get();

        foreach ($ventasCompletadasSinPago as $venta) {
            $inconsistencias[] = [
                'tipo' => 'venta_completada_sin_pago',
                'descripcion' => 'Venta marcada como completada sin pago aprobado',
                'venta_id' => $venta->id,
                'venta_estado' => $venta->estado,
                'total' => $venta->total,
                'severidad' => 'alta'
            ];
        }

        // 2. Ventas canceladas con pago aprobado
        $ventasCanceladasConPago = Venta::where('estado', 'cancelada')
            ->whereHas('pagos', function($query) {
                $query->where('estado', 'approved');
            })->get();

        foreach ($ventasCanceladasConPago as $venta) {
            $pago = $venta->pagos()->where('estado', 'approved')->first();
            $inconsistencias[] = [
                'tipo' => 'venta_cancelada_con_pago',
                'descripcion' => 'Venta cancelada pero con pago aprobado',
                'venta_id' => $venta->id,
                'pago_id' => $pago->id,
                'venta_estado' => $venta->estado,
                'pago_estado' => $pago->estado,
                'severidad' => 'critica'
            ];
        }

        // 3. Pagos aprobados sin venta completada
        $pagosAprobadosSinVenta = Pago::where('estado', 'approved')
            ->whereNotNull('venta_id')
            ->whereHas('venta', function($query) {
                $query->where('estado', '!=', 'completada');
            })->get();

        foreach ($pagosAprobadosSinVenta as $pago) {
            $inconsistencias[] = [
                'tipo' => 'pago_aprobado_sin_venta_completada',
                'descripcion' => 'Pago aprobado pero venta no completada',
                'pago_id' => $pago->id,
                'venta_id' => $pago->venta_id,
                'pago_estado' => $pago->estado,
                'venta_estado' => $pago->venta->estado,
                'severidad' => 'alta'
            ];
        }
        return $inconsistencias;
    }

    /**
     * Mostrar detalles de una inconsistencia
     */
    private function displayInconsistencia(array $inconsistencia): void
    {
        $severidadColor = match($inconsistencia['severidad']) {
            'critica' => 'error',
            'alta' => 'warn',
            'media' => 'comment',
            default => 'info'
        };

        $this->line("  🔸 <{$severidadColor}>[{$inconsistencia['tipo']}]</{$severidadColor}> {$inconsistencia['descripcion']}");

        if (isset($inconsistencia['venta_id'])) {
            $this->line("     Venta ID: {$inconsistencia['venta_id']}");
        }

        if (isset($inconsistencia['pago_id'])) {
            $this->line("     Pago ID: {$inconsistencia['pago_id']}");
        }

        $this->line('');
    }

    /**
     * Intentar correcciones automáticas
     */
    private function intentarCorreciones(array $inconsistencias): void
    {
        $this->info('🔧 Intentando correcciones automáticas...');
        $this->line('');

        $corregidas = 0;

        foreach ($inconsistencias as $inconsistencia) {
            try {
                switch ($inconsistencia['tipo']) {
                    case 'pago_aprobado_sin_venta_completada':
                        $venta = Venta::find($inconsistencia['venta_id']);
                        $this->info("   ℹ️  Las ventas ahora se crean como completadas automáticamente");
                        break;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Error corrigiendo {$inconsistencia['tipo']}: {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info("🎯 Correcciones completadas: {$corregidas}");
    }

    /**
     * Generar reporte detallado
     */
    private function generarReporte(array $inconsistencias): void
    {
        $this->info('📄 Generando reporte detallado...');

        $reporte = [
            'fecha_reporte' => now()->format('Y-m-d H:i:s'),
            'total_ventas' => Venta::count(),
            'total_pagos' => Pago::count(),
            'inconsistencias_encontradas' => count($inconsistencias),
            'inconsistencias' => $inconsistencias,
            'estadisticas' => [
                'ventas_completadas' => Venta::where('estado', 'completada')->count(),
                'ventas_canceladas' => Venta::where('estado', 'cancelada')->count(),
                'pagos_approved' => Pago::where('estado', 'approved')->count(),
                'pagos_pending' => Pago::where('estado', 'pending')->count(),
                'pagos_declined' => Pago::where('estado', 'declined')->count(),
            ]
        ];

        $filename = 'venta_pago_integrity_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path("logs/{$filename}");

        file_put_contents($filepath, json_encode($reporte, JSON_PRETTY_PRINT));

        $this->info("📄 Reporte guardado en: {$filepath}");

        // Log del reporte
        Log::info('Reporte de integridad Venta-Pago generado', [
            'archivo' => $filename,
            'inconsistencias' => count($inconsistencias)
        ]);
    }
}
