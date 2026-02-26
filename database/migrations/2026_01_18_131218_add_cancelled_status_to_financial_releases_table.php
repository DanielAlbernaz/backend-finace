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
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: Laravel usa VARCHAR para enums, então não precisamos alterar o tipo
            // Apenas adicionamos um CHECK constraint para garantir valores válidos
            // Primeiro, removemos o constraint antigo se existir
            DB::statement("
                ALTER TABLE financial_releases 
                DROP CONSTRAINT IF EXISTS financial_releases_status_check
            ");

            // Adiciona novo constraint incluindo 'cancelled'
            DB::statement("
                ALTER TABLE financial_releases 
                ADD CONSTRAINT financial_releases_status_check 
                CHECK (status IN ('pending', 'paid', 'overdue', 'cancelled'))
            ");
        } else {
            // Para MySQL, modifica o enum diretamente
            DB::statement("ALTER TABLE financial_releases MODIFY COLUMN status ENUM('pending', 'paid', 'overdue', 'cancelled') DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // Remove o constraint e recria sem 'cancelled'
            DB::statement("
                ALTER TABLE financial_releases 
                DROP CONSTRAINT IF EXISTS financial_releases_status_check
            ");

            DB::statement("
                ALTER TABLE financial_releases 
                ADD CONSTRAINT financial_releases_status_check 
                CHECK (status IN ('pending', 'paid', 'overdue'))
            ");
        } else {
            // Para MySQL, reverte o enum
            DB::statement("ALTER TABLE financial_releases MODIFY COLUMN status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending'");
        }
    }
};
