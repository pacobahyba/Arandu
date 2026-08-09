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
        // A tabela `usuarios` foi criada à mão no banco de produção e nunca teve
        // migration de criação. Sem esta guarda, migrar um banco novo (ou o
        // sqlite dos testes) quebra aqui.
        if (! Schema::hasTable('usuarios') || Schema::hasColumn('usuarios', 'verificado')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->boolean('verificado')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('usuarios')) {
            return;
        }

        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropColumn('verificado');
        });
    }
};
