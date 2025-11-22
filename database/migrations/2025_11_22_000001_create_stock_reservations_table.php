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
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->onDelete('cascade');
            $table->string('referencia_wompi')->index(); // Para asociar con el pago
            
            // Datos de la reserva
            $table->integer('cantidad_reservada');
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 10, 2);
            
            // Estados de la reserva
            $table->enum('estado', ['activa', 'confirmada', 'expirada', 'cancelada'])->default('activa');
            
            // Control de tiempo
            $table->timestamp('expira_en');
            $table->timestamp('confirmada_en')->nullable();
            $table->timestamp('cancelada_en')->nullable();
            
            // Metadatos
            $table->json('metadatos')->nullable(); // Para guardar info del carrito, usuario, etc.
            
            $table->timestamps();
            
            // Índices para optimizar consultas
            $table->index(['estado', 'expira_en']);
            $table->index(['referencia_wompi', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};