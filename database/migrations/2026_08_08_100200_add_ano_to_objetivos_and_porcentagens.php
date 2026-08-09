<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fecha uma divergência de schema: `objetivos.ano_objetivo` e
 * `porcentagem_objetivos.ano_porcentagem` são usados em todo o routes/api.php
 * mas foram adicionados à mão no banco de produção, sem migration.
 *
 * Guardado por hasColumn: no-op no banco existente, e permite subir um banco
 * novo (ou o sqlite dos testes) com o mesmo schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('objetivos') && ! Schema::hasColumn('objetivos', 'ano_objetivo')) {
            Schema::table('objetivos', function (Blueprint $table) {
                $table->integer('ano_objetivo')->nullable();
            });
        }

        if (Schema::hasTable('porcentagem_objetivos') && ! Schema::hasColumn('porcentagem_objetivos', 'ano_porcentagem')) {
            Schema::table('porcentagem_objetivos', function (Blueprint $table) {
                $table->integer('ano_porcentagem')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Não removemos: as colunas existem em produção desde antes desta migration.
    }
};
