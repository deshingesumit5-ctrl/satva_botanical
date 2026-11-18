<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('monthly_expenses', 'plant_id')) {
                $table->foreignId('plant_id')->nullable()->after('year')->constrained('plants')->nullOnDelete();
            }
            if (!Schema::hasColumn('monthly_expenses', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_expenses', 'plant_id')) {
                $table->dropForeign(['plant_id']);
                $table->dropColumn('plant_id');
            }
            if (Schema::hasColumn('monthly_expenses', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};
