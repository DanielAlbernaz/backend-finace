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
        Schema::table('payment_methods', function (Blueprint $table) {
            // Remove índice da coluna type antes de remover a coluna
            $table->dropIndex(['type']);
            
            // Remove a coluna type
            $table->dropColumn('type');
            
            // Adiciona a coluna is_custom
            $table->boolean('is_custom')->default(false)->after('name');
            
            // Adiciona índice para melhor performance
            $table->index('is_custom');
        });

        // Atualiza registros existentes: métodos padrão (finance_account_id = null) → is_custom = false
        // Métodos personalizados (finance_account_id != null) → is_custom = true
        DB::table('payment_methods')->whereNull('finance_account_id')->update(['is_custom' => false]);
        DB::table('payment_methods')->whereNotNull('finance_account_id')->update(['is_custom' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            // Remove índice de is_custom
            $table->dropIndex(['is_custom']);
            
            // Remove a coluna is_custom
            $table->dropColumn('is_custom');
            
            // Adiciona a coluna type de volta
            $table->enum('type', ['cash', 'pix', 'debit', 'credit_card', 'wallet', 'other'])->default('other')->after('name');
            
            // Adiciona índice para type
            $table->index('type');
        });
    }
};
