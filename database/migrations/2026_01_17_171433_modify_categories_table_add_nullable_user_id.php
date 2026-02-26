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
        // Remove a foreign key antiga
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Altera a coluna usando sintaxe compatível com PostgreSQL e MySQL
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL - Remove NOT NULL constraint
            DB::statement('ALTER TABLE categories ALTER COLUMN user_id DROP NOT NULL');
        } else {
            // MySQL/MariaDB
            DB::statement('ALTER TABLE categories MODIFY user_id BIGINT UNSIGNED NULL');
        }

        // Recria a foreign key com nullable
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Remove a foreign key
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        // Torna user_id obrigatório novamente
        $driver = DB::getDriverName();
        
        if ($driver === 'pgsql') {
            // PostgreSQL
            DB::statement('ALTER TABLE categories ALTER COLUMN user_id SET NOT NULL');
        } else {
            // MySQL/MariaDB
            DB::statement('ALTER TABLE categories MODIFY user_id BIGINT UNSIGNED NOT NULL');
        }

        // Recria a foreign key sem nullable
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
