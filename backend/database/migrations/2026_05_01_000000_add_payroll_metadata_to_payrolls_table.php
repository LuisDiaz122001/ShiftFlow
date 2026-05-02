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
        Schema::table('payrolls', function (Blueprint $table) {
            $afterColumn = Schema::hasColumn('payrolls', 'payroll_cycle_id') ? 'payroll_cycle_id' : 'employee_id';
            
            if (!Schema::hasColumn('payrolls', 'period_start')) {
                $table->date('period_start')->nullable()->after($afterColumn);
            }
            if (!Schema::hasColumn('payrolls', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
            if (!Schema::hasColumn('payrolls', 'total_hours')) {
                $table->decimal('total_hours', 10, 2)->default(0)->after('period_end');
            }
            if (!Schema::hasColumn('payrolls', 'hourly_rate')) {
                $table->decimal('hourly_rate', 12, 2)->default(0)->after('total_hours');
            }
            if (!Schema::hasColumn('payrolls', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0)->after('hourly_rate');
            }
            if (!Schema::hasColumn('payrolls', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('estado');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'period_start',
                'period_end',
                'total_hours',
                'hourly_rate',
                'total_amount',
                'paid_at',
            ]);
        });
    }
};
