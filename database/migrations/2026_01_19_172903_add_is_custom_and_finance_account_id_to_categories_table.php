<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Adiciona finance_account_id (nullable para categorias padrão)
            $table->unsignedBigInteger('finance_account_id')->nullable()->after('user_id');

            // Adiciona is_custom (boolean)
            $table->boolean('is_custom')->default(false)->after('title');

            // Foreign key para finance_accounts
            $table->foreign('finance_account_id')
                ->references('id')
                ->on('finance_accounts')
                ->onDelete('cascade');

            // Índices para melhor performance
            $table->index(['finance_account_id', 'is_custom']);
            $table->index('is_custom');
        });

        // Atualiza registros existentes:
        // Categorias padrão (user_id = null) → is_custom = false, finance_account_id = null
        // Categorias personalizadas (user_id != null) → is_custom = true, finance_account_id = null (será atualizado depois)
        DB::table('categories')->whereNull('user_id')->update([
            'is_custom' => false,
            'finance_account_id' => null
        ]);

        // Para categorias personalizadas existentes, precisamos obter o finance_account_id do usuário
        // Mas como não temos essa informação direta, vamos marcar como is_custom = true
        // e deixar finance_account_id = null por enquanto (será atualizado quando o usuário criar novas categorias)
        DB::table('categories')->whereNotNull('user_id')->update([
            'is_custom' => true,
            'finance_account_id' => null // Será atualizado quando necessário
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Remove índices
            $table->dropIndex(['finance_account_id', 'is_custom']);
            $table->dropIndex(['is_custom']);

            // Remove foreign key
            $table->dropForeign(['finance_account_id']);

            // Remove colunas
            $table->dropColumn(['finance_account_id', 'is_custom']);
        });
    }
};
