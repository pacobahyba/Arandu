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
        Schema::create('periodos_avaliacao', function (Blueprint $table) {
            $table->integer('id_periodo_avaliacao', true);
            $table->date('dt_primeiro_dia');
            $table->date('dt_ultimo_dia')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos_avaliacao');
    }
};
