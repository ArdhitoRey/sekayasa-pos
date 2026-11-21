<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Leads & Client Organizations
        Schema::create('client_leads', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('business_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('business_type')->nullable(); // F&B, Retail, Barbershop, Apotek, etc.
            $table->string('source')->default('website_inquiry');
            $table->enum('status', [
                'new_lead',
                'consultation_scheduled',
                'consultation_completed',
                'discovery',
                'process_mapped',
                'proposal_draft',
                'proposal_sent',
                'revision_requested',
                'scope_approved',
                'contract_pending',
                'payment_pending',
                'ready_for_dev',
                'in_dev',
                'internal_qa',
                'client_uat',
                'uat_approved',
                'ready_to_deploy',
                'deploying',
                'live',
                'monthly_service_active',
                'suspended',
                'terminated'
            ])->default('new_lead');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Consultations
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('pic_name');
            $table->text('consultation_notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });

        // 3. Discovery Sessions (As-Is & To-Be)
        Schema::create('discovery_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->text('as_is_summary')->nullable();
            $table->text('to_be_summary')->nullable();
            $table->text('business_flow_notes')->nullable();
            $table->string('target_hardware')->nullable(); // e.g. Android Tablet + Thermal 80mm
            $table->decimal('budget_estimation', 14, 2)->default(0);
            $table->timestamps();
        });

        // 4. Custom Requirements Register (SOW Traceability)
        Schema::create('project_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->string('req_code', 20); // e.g. REQ-FNB-001
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('high');
            $table->string('category')->default('sales'); // sales, inventory, purchasing, staff, report, hardware, integration, offline
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        // 5. Proposals
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->string('proposal_number')->unique();
            $table->integer('version')->default(1);
            $table->decimal('development_cost', 14, 2);
            $table->decimal('monthly_maintenance_cost', 14, 2);
            $table->text('deliverables')->nullable();
            $table->integer('timeline_weeks')->default(4);
            $table->enum('status', ['draft', 'sent', 'approved', 'rejected'])->default('draft');
            $table->timestamps();
        });

        // 6. Contracts & 100% Payment Gate
        Schema::create('project_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('client_leads')->cascadeOnDelete();
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->nullOnDelete();
            $table->string('contract_number')->unique();
            $table->enum('type', ['development', 'monthly_service'])->default('development');
            $table->decimal('total_amount', 14, 2);
            $table->boolean('is_signed')->default(false);
            $table->timestamp('signed_at')->nullable();
            $table->enum('payment_status', ['unpaid', 'verified_100_percent'])->default('unpaid');
            $table->timestamp('payment_verified_at')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_contracts');
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('project_requirements');
        Schema::dropIfExists('discovery_sessions');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('client_leads');
    }
};
