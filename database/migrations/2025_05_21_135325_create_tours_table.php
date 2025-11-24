<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->enum('categoria', ['NACIONAL', 'INTERNACIONAL']);
            $table->text('incluye')->nullable();
            $table->text('no_incluye')->nullable();
            $table->integer('cupo_min');
            $table->integer('cupo_max');
            $table->string('punto_salida', 200);
            $table->datetime('fecha_salida');
            $table->datetime('fecha_regreso');
            $table->enum('estado', ['DISPONIBLE', 'COMPLETO', 'EN_CURSO', 'FINALIZADO', 'CANCELADA', 'REPROGRAMADA'])->default('DISPONIBLE');
            $table->decimal('precio', 6, 2);
            // llave foránea a transportes
            $table->unsignedBigInteger('transporte_id');
            $table->foreign('transporte_id')->references('id')->on('transportes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
