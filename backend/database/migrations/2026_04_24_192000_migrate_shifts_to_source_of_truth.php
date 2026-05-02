<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->decimal('total_hours', 10, 2)->default(0)->after('fecha_fin');
            $table->decimal('diurnas_hours', 10, 2)->default(0)->after('total_hours');
            $table->decimal('nocturnas_hours', 10, 2)->default(0)->after('diurnas_hours');
            $table->decimal('total_pago', 12, 2)->default(0)->after('nocturnas_hours');
        });

        if (Schema::hasTable('shift_calculations')) {
            $calculations = DB::table('shift_calculations')->get();

            foreach ($calculations as $calc) {
                DB::table('shifts')->where('id', $calc->shift_id)->update([
                    'diurnas_hours' => $calc->horas_diurnas + $calc->horas_extra_diurnas,
                    'nocturnas_hours' => $calc->horas_nocturnas + $calc->horas_extra_nocturnas,
                    'total_hours' => $calc->horas_diurnas + $calc->horas_nocturnas + $calc->horas_extra_diurnas + $calc->horas_extra_nocturnas,
                    'total_pago' => $calc->valor_total,
                ]);
            }
        }

        if (! Schema::hasTable('payroll_shift')) {
            Schema::create('payroll_shift', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
                $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['payroll_id', 'shift_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_shift');

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['total_hours', 'diurnas_hours', 'nocturnas_hours', 'total_pago']);
        });
    }
};
