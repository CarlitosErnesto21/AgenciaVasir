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
        Schema::create('detalles_reservas_hoteles', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha_entrada');
            $table->dateTime('fecha_salida');
            $table->integer('cantidad_persona');
            $table->integer('cantidad_habitacion');
            $table->decimal('subtotal', 8, 2);
            // llaves foraneeas a reservas y hoteles
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('hotel_id');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('hotel_id')->references('id')->on('hoteles')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_reservas_hoteles');
    }
};
