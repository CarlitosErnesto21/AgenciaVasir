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
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->datetime('fecha_movimiento');
            $table->integer('cantidad');
            $table->enum('tipo_movimiento', ['ENTRADA', 'SALIDA']);
            $table->string('motivo', 100)->nullable();
            $table->text('observacion')->nullable();

            //Llaves foráneas
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('productos')->onDelete('cascade');

            //Relación polimórfica
            $table->unsignedBigInteger('referenciable_id')->nullable();
            $table->string('referenciable_type')->nullable();
            $table->index(['referenciable_type', 'referenciable_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
