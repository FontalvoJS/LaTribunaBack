<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('name_modifyed_at')->nullable();
            $table->string('parche')->nullable();
            $table->string('active')->nullable();
            $table->string('club')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name_modifyed_at');
            $table->dropColumn('parche');
            $table->dropColumn('active');
            $table->dropColumn('club');
        });
    }
};
