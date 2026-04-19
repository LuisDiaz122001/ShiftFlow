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
            $table->string('role')->default('employee')->after('email');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->string('status')->default('approved')->after('payroll_cycle_id');
            $table->boolean('is_voided')->default(false)->after('status');
            $table->timestamp('voided_at')->nullable()->after('is_voided');
            
            // Auditoría
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Trazabilidad de reemplazo inatacable
            $table->foreignId('voids_shift_id')->nullable()->unique()->constrained('shifts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        Schema::table('shifts', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['voided_by']);
            $table->dropForeign(['voids_shift_id']);
            
            $table->dropColumn([
                'status', 'is_voided', 'voided_at', 
                'approved_by', 'approved_at', 
                'rejected_by', 'rejected_at', 
                'voided_by', 'voids_shift_id'
            ]);
        });
    }
};
