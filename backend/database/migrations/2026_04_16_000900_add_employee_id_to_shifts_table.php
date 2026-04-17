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
        Schema::table('shifts', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->after('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->index(['employee_id', 'fecha_inicio']);
        });

        $employeeByUser = DB::table('employees')
            ->whereNotNull('user_id')
            ->pluck('id', 'user_id');

        DB::table('shifts')
            ->select('id', 'user_id')
            ->orderBy('id')
            ->get()
            ->each(function (object $shift) use ($employeeByUser): void {
                $employeeId = $employeeByUser[$shift->user_id] ?? null;

                if ($employeeId !== null) {
                    DB::table('shifts')
                        ->where('id', $shift->id)
                        ->update(['employee_id' => $employeeId]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropIndex('shifts_employee_id_fecha_inicio_index');
            $table->dropConstrainedForeignId('employee_id');
        });
    }
};
