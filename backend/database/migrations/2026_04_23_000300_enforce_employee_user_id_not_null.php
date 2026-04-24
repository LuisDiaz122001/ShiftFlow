<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $employeesWithoutUser = DB::table('employees')
            ->whereNull('user_id')
            ->count();

        if ($employeesWithoutUser > 0) {
            throw new RuntimeException(
                'No se puede endurecer employees.user_id a NOT NULL porque existen empleados sin usuario asociado.'
            );
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
