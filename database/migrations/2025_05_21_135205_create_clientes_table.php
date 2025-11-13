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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_identificacion', 25);
            $table->date('fecha_nacimiento');
            $table->enum('genero', ['MASCULINO', 'FEMENINO']);
            $table->enum('tipo_documento', ['DUI', 'PASAPORTE']);
            $table->string('direccion', 200);
            $table->string('telefono', 30)->unique();
            // Llave foranea a la tabla users
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
