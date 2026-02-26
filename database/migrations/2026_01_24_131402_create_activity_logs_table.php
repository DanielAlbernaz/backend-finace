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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('finance_account_id')->constrained()->onDelete('cascade');
            $table->morphs('loggable'); // Cria loggable_type e loggable_id
            $table->string('action'); // created, updated, deleted
            $table->string('ip_address', 45)->nullable(); // IPv6 pode ter até 45 caracteres
            $table->json('changes')->nullable(); // Armazena dados alterados (old/new values)
            $table->text('description')->nullable(); // Descrição opcional da ação
            $table->timestamps();

            // Índices para melhor performance
            // Nota: morphs() já cria índice para loggable_type e loggable_id
            // Foreign keys também criam índices automaticamente no PostgreSQL
            $table->index('action');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
