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
            $table->unsignedBigInteger('payment_method_id')->nullable()->after('category_id');

            // Foreign key para payment_methods
            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('set null');

            // Índice para melhor performance em filtros
            $table->index('payment_method_id');
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
            $table->dropForeign(['payment_method_id']);
            $table->dropIndex(['payment_method_id']);
            $table->dropColumn('payment_method_id');
        });
    }
};
