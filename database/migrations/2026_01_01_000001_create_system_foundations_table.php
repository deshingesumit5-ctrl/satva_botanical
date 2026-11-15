<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('Sattva Botanical');
            $table->string('logo')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('website')->nullable();
            $table->string('default_currency', 10)->default('INR');
            $table->string('date_format', 20)->default('d-m-Y');
            $table->string('financial_year_start', 10)->default('04-01');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('application_name')->default('Sattva Botanical');
            $table->string('default_timezone')->default('Asia/Kolkata');
            $table->string('default_currency', 10)->default('INR');
            $table->string('default_date_format', 20)->default('d-m-Y');
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->unsignedTinyInteger('quantity_precision')->default(3);
            $table->string('default_language', 10)->default('en');
            $table->boolean('email_notifications')->default(false);
            $table->unsignedInteger('attachment_size_limit_mb')->default(10);
            $table->unsignedInteger('session_timeout_minutes')->default(120);
            $table->string('inventory_valuation_method', 30)->default('Weighted Average');
            $table->timestamps();
        });

        Schema::create('document_numberings', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 60)->unique();
            $table->string('prefix', 20);
            $table->unsignedInteger('starting_number')->default(1);
            $table->unsignedInteger('current_number')->default(0);
            $table->boolean('financial_year_reset')->default(true);
            $table->string('number_format', 50)->default('{PREFIX}/{FY}/{NUM}');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });

        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->string('module', 60)->index();
            $table->string('record_reference', 100)->nullable()->index();
            $table->string('action', 50)->index();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('role_code', 50)->nullable()->index();
            $table->string('type', 50)->default('general')->index();
            $table->string('title');
            $table->text('message');
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false)->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_trails');
        Schema::dropIfExists('document_numberings');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('company_settings');
    }
};
