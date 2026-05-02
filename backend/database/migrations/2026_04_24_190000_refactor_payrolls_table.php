<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payrolls', 'estado')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->string('estado', 20)->default('pending')->after('fecha_pago');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payrolls', 'estado')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }
};
