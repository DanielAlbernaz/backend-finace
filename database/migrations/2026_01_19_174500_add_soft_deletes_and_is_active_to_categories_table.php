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
        Schema::table('categories', function (Blueprint $table) {
            // Adiciona coluna is_active
            $table->boolean('is_active')->default(true)->after('is_custom');
            
            // Adiciona soft deletes
            $table->softDeletes();
            
            // Índice para melhor performance
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('categories', function (Blueprint $table) {
            // Remove índice
            $table->dropIndex(['is_active']);
            
            // Remove soft deletes
            $table->dropSoftDeletes();
            
            // Remove coluna is_active
            $table->dropColumn('is_active');
        });
    }
};
