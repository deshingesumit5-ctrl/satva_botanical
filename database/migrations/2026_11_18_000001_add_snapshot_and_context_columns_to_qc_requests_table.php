<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('qc_requests', 'product_id')) {
                $table->unsignedBigInteger('product_id')->nullable()->after('batch_stage_execution_id');
            }
            if (!Schema::hasColumn('qc_requests', 'recipe_version_id')) {
                $table->unsignedBigInteger('recipe_version_id')->nullable()->after('product_id');
            }
            if (!Schema::hasColumn('qc_requests', 'stage_id')) {
                $table->unsignedBigInteger('stage_id')->nullable()->after('recipe_version_id');
            }
            if (!Schema::hasColumn('qc_requests', 'readings_snapshot')) {
                $table->json('readings_snapshot')->nullable()->after('rework_instructions');
            }
            if (!Schema::hasColumn('qc_requests', 'outputs_snapshot')) {
                $table->json('outputs_snapshot')->nullable()->after('readings_snapshot');
            }
            if (!Schema::hasColumn('qc_requests', 'parent_qc_request_id')) {
                $table->unsignedBigInteger('parent_qc_request_id')->nullable()->after('outputs_snapshot');
            }
            if (!Schema::hasColumn('qc_requests', 'retest_qc_request_id')) {
                $table->unsignedBigInteger('retest_qc_request_id')->nullable()->after('parent_qc_request_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('qc_requests', function (Blueprint $table) {
            $cols = [
                'product_id',
                'recipe_version_id',
                'stage_id',
                'readings_snapshot',
                'outputs_snapshot',
                'parent_qc_request_id',
                'retest_qc_request_id',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('qc_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
