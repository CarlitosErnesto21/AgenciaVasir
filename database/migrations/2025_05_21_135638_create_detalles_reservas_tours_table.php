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
        Schema::create('detalles_reservas_tours', function (Blueprint $table) {
            $table->id();
            $table->integer('cupos_reservados');
            $table->decimal('precio_unitario', 5, 2);
            $table->decimal('precio_total', 6, 2);
            // llave foranea a reservas y tours
            $table->unsignedBigInteger('reserva_id');
            $table->unsignedBigInteger('tour_id');
            $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('cascade');
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalles_reservas_tours');
    }
};
