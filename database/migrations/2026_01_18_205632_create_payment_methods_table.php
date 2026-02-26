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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('finance_account_id')->nullable();
            $table->string('name');
            $table->enum('type', ['cash', 'pix', 'debit', 'credit_card', 'wallet', 'other'])->default('other');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key para finance_accounts (opcional)
            $table->foreign('finance_account_id')
                ->references('id')
                ->on('finance_accounts')
                ->onDelete('cascade');

            // Índices para melhor performance
            $table->index(['finance_account_id', 'is_active']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
