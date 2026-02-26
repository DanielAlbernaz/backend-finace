<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('financial_releases', function (Blueprint $table) {
            // Remove a foreign key antiga de user_id primeiro
            $table->dropForeign(['user_id']);
            
            // Adiciona finance_account_id
            $table->foreignId('finance_account_id')->after('category_id')->constrained('finance_accounts')->onDelete('cascade');
            
            // Adiciona created_by para auditoria
            $table->foreignId('created_by')->after('finance_account_id')->constrained('users')->onDelete('cascade');
            
            // Remove a coluna user_id antiga
            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financial_releases', function (Blueprint $table) {
            // Remove as novas colunas
            $table->dropForeign(['finance_account_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['finance_account_id', 'created_by']);
            
            // Restaura user_id
            $table->foreignId('user_id')->after('category_id')->constrained('users')->onDelete('cascade');
        });
    }
};
