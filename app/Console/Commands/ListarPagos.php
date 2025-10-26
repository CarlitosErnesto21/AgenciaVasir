<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pago;

class ListarPagos extends Command
{
    protected $signature = 'list:pagos';
    protected $description = 'Lista todos los pagos en la base de datos';

    public function handle()
    {
        $pagos = Pago::all();

        if ($pagos->isEmpty()) {
            $this->info("No hay pagos en la base de datos");
            return;
        }

        $this->info("📋 Pagos encontrados:");
        $this->info("ID | Estado | Referencia | Venta ID | Monto");
        $this->info("---|--------|------------|----------|------");

        foreach ($pagos as $pago) {
            $this->info(sprintf(
                "%s | %s | %s | %s | %s",
                $pago->id,
                $pago->estado,
                $pago->referencia_wompi ?? 'NULL',
                $pago->venta_id ?? 'NULL',
                $pago->monto ?? 'NULL'
            ));
        }
    }
}
