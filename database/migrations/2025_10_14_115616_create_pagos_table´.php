<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // Relaciones con ventas y reservas (una de las dos debe estar presente)
            $table->unsignedBigInteger('venta_id')->nullable();
            $table->unsignedBigInteger('reserva_id')->nullable();

            // Información del pago
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('COP');
            $table->string('metodo_pago', 50); // tarjeta_credito, etc.
            $table->string('email_cliente');

            // Referencias de Wompi
            $table->string('referencia_wompi', 100)->unique(); // Referencia que enviamos a Wompi
            $table->string('wompi_transaction_id', 100)->nullable(); // ID de transacción de Wompi
            $table->string('wompi_reference', 100)->nullable(); // Referencia que nos devuelve Wompi
            $table->string('wompi_payment_link_id', 100)->nullable(); // ID del payment link de Wompi
            $table->text('wompi_payment_link')->nullable(); // URL del payment link de Wompi

            // Estado del pago
            $table->enum('estado', [
                'pending', 'approved', 'declined', 'voided', 'error', 'failed'
            ])->default('pending');

            // Datos adicionales
            $table->text('mensaje_error')->nullable();
            $table->json('response_data')->nullable(); // Respuesta completa de Wompi
            $table->json('productos_detalle')->nullable(); // Detalle de productos para payment links

            // Timestamps
            $table->timestamps();

            // Índices y llaves foráneas
            $table->foreign('venta_id')->references('id')->on('ventas')->onDelete('cascade');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');

            // Índices para consultas rápidas
            $table->index(['estado', 'created_at']);
            $table->index('wompi_transaction_id');
            $table->index('referencia_wompi');
            $table->index('wompi_payment_link_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
