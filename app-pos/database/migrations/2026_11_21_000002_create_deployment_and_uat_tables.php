<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. UAT Test Cases & Sign-Off (OPS-027 s/d OPS-031)
        Schema::create('uat_test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->foreignId('requirement_id')->nullable()->constrained('project_requirements')->nullOnDelete();
            $table->string('test_title');
            $table->text('scenario');
            $table->text('expected_result');
            $table->enum('status', ['pending', 'pass', 'fail', 'blocked'])->default('pending');
            $table->text('client_feedback')->nullable();
            $table->timestamp('tested_at')->nullable();
            $table->timestamps();
        });

        // 2. Deployment Targets (OPS-032 / Bab 10.5)
        Schema::create('deployment_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->enum('environment', ['staging', 'production'])->default('staging');
            $table->enum('provider', ['vps_dedicated', 'render_preview', 'railway', 'client_server'])->default('vps_dedicated');
            $table->string('host_url');
            $table->string('git_repo_url')->nullable();
            $table->string('git_branch')->default('main');
            $table->boolean('has_backup_schedule')->default(true);
            $table->boolean('tls_ssl_active')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Deployment Runs & Audit Log (One-Click Deploy Pipeline)
        Schema::create('deployment_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->foreignId('target_id')->nullable()->constrained('deployment_targets')->nullOnDelete();
            $table->enum('environment', ['staging', 'production'])->default('staging');
            $table->string('release_version'); // e.g. v1.0.0
            $table->string('commit_sha', 40)->nullable();
            $table->string('operator_name');
            $table->enum('status', [
                'queued',
                'building',
                'testing',
                'backing_up',
                'deploying',
                'migrating',
                'health_check',
                'successful',
                'failed',
                'rolled_back'
            ])->default('queued');
            $table->longText('log_output')->nullable();
            $table->string('backup_reference')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 4. Monthly Service Subscriptions (Bab 12)
        Schema::create('service_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->enum('plan_tier', ['basic', 'standard', 'premium'])->default('standard');
            $table->decimal('monthly_fee', 14, 2);
            $table->dateTime('starts_at');
            $table->dateTime('next_billing_at');
            $table->enum('status', ['active', 'overdue', 'suspended', 'terminated'])->default('active');
            $table->timestamps();
        });

        // 5. Support Tickets (Bab 12 / OPS-025)
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('service_subscriptions');
        Schema::dropIfExists('deployment_runs');
        Schema::dropIfExists('deployment_targets');
        Schema::dropIfExists('uat_test_cases');
    }
};
