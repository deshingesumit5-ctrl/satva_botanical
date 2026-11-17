<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recipe_stages')) {
            Schema::table('recipe_stages', function (Blueprint $table) {
                if (!Schema::hasColumn('recipe_stages', 'min_repetitions')) {
                    $table->unsignedInteger('min_repetitions')->default(1)->after('repeat_config');
                }
                if (!Schema::hasColumn('recipe_stages', 'acceptance_parameter_ids')) {
                    $table->json('acceptance_parameter_ids')->nullable()->after('min_repetitions');
                }
                if (!Schema::hasColumn('recipe_stages', 'expected_process_conditions')) {
                    $table->json('expected_process_conditions')->nullable()->after('acceptance_parameter_ids');
                }
                if (!Schema::hasColumn('recipe_stages', 'supervisor_auth_required')) {
                    $table->boolean('supervisor_auth_required')->default(false)->after('qc_required');
                }
            });
        }

        if (Schema::hasTable('batch_stage_executions')) {
            Schema::table('batch_stage_executions', function (Blueprint $table) {
                if (!Schema::hasColumn('batch_stage_executions', 'supervisor_authorized_by')) {
                    $table->foreignId('supervisor_authorized_by')->nullable()->after('qc_status')->constrained('users')->nullOnDelete();
                }
                if (!Schema::hasColumn('batch_stage_executions', 'supervisor_authorized_at')) {
                    $table->dateTime('supervisor_authorized_at')->nullable()->after('supervisor_authorized_by');
                }
                if (!Schema::hasColumn('batch_stage_executions', 'supervisor_auth_remarks')) {
                    $table->text('supervisor_auth_remarks')->nullable()->after('supervisor_authorized_at');
                }
                if (!Schema::hasColumn('batch_stage_executions', 'actual_hold_time_minutes')) {
                    $table->unsignedInteger('actual_hold_time_minutes')->nullable()->after('duration_minutes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('batch_stage_executions')) {
            Schema::table('batch_stage_executions', function (Blueprint $table) {
                if (Schema::hasColumn('batch_stage_executions', 'supervisor_authorized_by')) {
                    $table->dropForeign(['supervisor_authorized_by']);
                    $table->dropColumn(['supervisor_authorized_by', 'supervisor_authorized_at', 'supervisor_auth_remarks', 'actual_hold_time_minutes']);
                }
            });
        }

        if (Schema::hasTable('recipe_stages')) {
            Schema::table('recipe_stages', function (Blueprint $table) {
                $cols = array_filter(['min_repetitions', 'acceptance_parameter_ids', 'expected_process_conditions', 'supervisor_auth_required'], fn($c) => Schema::hasColumn('recipe_stages', $c));
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
