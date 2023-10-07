<?php

use App\Models\Category;
use App\Models\Installment;
use App\Models\User;
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
        Schema::create('financial_releases', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['expense', 'revenue']);
            $table->decimal('value', 11 ,2);
            $table->date('date');
            $table->date('payment_date')->nullable();
            $table->longtext('descrition')->nullable();
            $table->longtext('observation')->nullable();
            $table->enum('repetition', ['only', 'installments', 'fixed']);
            $table->string('portion')->nullable();
            $table->foreignId('category_id')->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Installment::class)->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('financial_releases');
    }
};
