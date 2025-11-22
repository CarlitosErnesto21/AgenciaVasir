<?php

namespace App\Console\Commands;

use App\Models\Pago;
use App\Models\StockReservation;
use Illuminate\Console\Command;

class DebugReservationConfirmation extends Command
{
    protected $signature = 'debug:reservation-confirmation {referencia}';
    protected $description = 'Debug why a reservation was not confirmed properly';

    public function handle()
    {
        $referencia = $this->argument('referencia');
        
        $this->info("🔍 DEPURANDO CONFIRMACIÓN DE RESERVA: $referencia");
        $this->line("");

        // Verificar pago
        $pago = Pago::where('referencia_wompi', $referencia)->first();
        if (!$pago) {
            $this->error("❌ No se encontró pago con referencia: $referencia");
            return 1;
        }

        $this->info("💳 PAGO ENCONTRADO:");
        $this->table(['Campo', 'Valor'], [
            ['ID', $pago->id],
            ['Estado', $pago->estado],
            ['Venta ID', $pago->venta_id],
            ['Monto', $pago->monto],
            ['Creado', $pago->created_at],
            ['Actualizado', $pago->updated_at],
        ]);

        // Verificar reservas
        $reservas = StockReservation::where('referencia_wompi', $referencia)->get();
        
        $this->info("📦 RESERVAS RELACIONADAS:");
        if ($reservas->isEmpty()) {
            $this->error("❌ No se encontraron reservas con esa referencia");
            return 1;
        }

        $this->table(['ID', 'Producto ID', 'Estado', 'Cantidad', 'Creada', 'Confirmada', 'Expirada'], 
            $reservas->map(function($reserva) {
                return [
                    $reserva->id,
                    $reserva->producto_id,
                    $reserva->estado,
                    $reserva->cantidad_reservada,
                    $reserva->created_at->format('H:i:s'),
                    $reserva->confirmada_en?->format('H:i:s') ?? 'No',
                    $reserva->expira_en < now() ? 'SÍ' : 'No'
                ];
            })->toArray()
        );

        // Verificar si las reservas estaban activas cuando se intentó confirmar
        $this->info("🕒 ANÁLISIS TEMPORAL:");
        foreach ($reservas as $reserva) {
            $estadoAlMomento = $reserva->expira_en > $pago->updated_at ? 'Activa' : 'Expirada';
            $this->line("Reserva {$reserva->id}: Estado al momento del pago: $estadoAlMomento");
            
            if ($reserva->estado === 'expirada' && $pago->estado === 'approved') {
                $this->warn("⚠️  Reserva {$reserva->id} expiró ANTES de confirmarse el pago");
                $this->line("   Expiró: {$reserva->expira_en}");
                $this->line("   Pago confirmado: {$pago->updated_at}");
            }
        }

        // Sugerir acciones
        $this->line("");
        $this->info("💡 ACCIONES SUGERIDAS:");
        
        $reservasExpiradas = $reservas->where('estado', 'expirada');
        if ($reservasExpiradas->isNotEmpty() && $pago->estado === 'approved') {
            $this->line("1. Las reservas expiraron antes de confirmarse el pago");
            $this->line("2. Considera aumentar el tiempo de expiración de reservas");
            $this->line("3. O mejorar la velocidad del proceso de pago");
        }

        $reservasActivas = $reservas->where('estado', 'activa');
        if ($reservasActivas->isNotEmpty()) {
            $this->line("1. Hay reservas aún activas que pueden confirmarse");
            $this->ask("¿Confirmar manualmente las reservas activas? (presiona Enter para continuar)");
            
            foreach ($reservasActivas as $reserva) {
                try {
                    $reserva->confirmar();
                    $this->info("✅ Reserva {$reserva->id} confirmada manualmente");
                } catch (\Exception $e) {
                    $this->error("❌ Error al confirmar reserva {$reserva->id}: {$e->getMessage()}");
                }
            }
        }

        return 0;
    }
}