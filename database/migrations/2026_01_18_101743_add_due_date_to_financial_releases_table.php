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
        // Verifica o driver do banco de dados
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL: adiciona coluna nullable primeiro
            DB::statement("ALTER TABLE financial_releases ADD COLUMN due_date DATE NULL");
            
            // Atualiza registros existentes: usa 'date' como valor padrão para 'due_date'
            DB::statement("UPDATE financial_releases SET due_date = date WHERE due_date IS NULL");
            
            // Torna a coluna NOT NULL
            DB::statement("ALTER TABLE financial_releases ALTER COLUMN due_date SET NOT NULL");
        } else {
            // MySQL e outros: usa Schema Builder
            Schema::table('financial_releases', function (Blueprint $table) {
                // Adiciona a coluna due_date como nullable temporariamente
                $table->date('due_date')->nullable()->after('date');
            });

            // Atualiza registros existentes: usa 'date' como valor padrão para 'due_date'
            DB::statement("
                UPDATE financial_releases
                SET due_date = date
                WHERE due_date IS NULL
            ");

            // Agora torna a coluna NOT NULL usando SQL direto (evita problema com ->change())
            DB::statement("ALTER TABLE financial_releases MODIFY due_date DATE NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('financial_releases', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
