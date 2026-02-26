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
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->foreignId('plan_id')->after('owner_id')->nullable()->constrained('plans')->onDelete('restrict');
            $table->enum('subscription_status', ['active', 'inactive', 'expired', 'cancelled'])->default('active')->after('plan_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('subscription_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('finance_accounts', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'subscription_status', 'subscription_ends_at']);
        });
    }
};
