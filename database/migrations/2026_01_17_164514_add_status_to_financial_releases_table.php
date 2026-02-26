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
        Schema::table('financial_releases', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending')->after('payment_date');
        });

        // Atualiza status dos registros existentes
        DB::statement("
            UPDATE financial_releases
            SET status = CASE
                WHEN payment_date IS NOT NULL THEN 'paid'
                WHEN date < CURRENT_DATE  THEN 'overdue'
                ELSE 'pending'
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financial_releases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
