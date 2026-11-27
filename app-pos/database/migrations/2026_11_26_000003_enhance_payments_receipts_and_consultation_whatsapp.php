<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_payments', 'receipt_number')) {
                $table->string('receipt_number')->nullable()->unique()->after('invoice_number');
            }
            if (!Schema::hasColumn('subscription_payments', 'plan_name_snapshot')) {
                $table->string('plan_name_snapshot')->nullable()->after('plan_id');
            }
            if (!Schema::hasColumn('subscription_payments', 'billing_starts_at')) {
                $table->timestamp('billing_starts_at')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('subscription_payments', 'billing_expires_at')) {
                $table->timestamp('billing_expires_at')->nullable()->after('billing_starts_at');
            }
        });

        Schema::table('client_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('client_leads', 'scale')) {
                $table->string('scale', 100)->nullable()->after('business_type');
            }
            if (!Schema::hasColumn('client_leads', 'subscription_plan_snapshot')) {
                $table->string('subscription_plan_snapshot', 150)->nullable()->after('scale');
            }
            if (!Schema::hasColumn('client_leads', 'consultation_time_slot')) {
                $table->string('consultation_time_slot', 40)->nullable()->after('preferred_consultation_at');
            }
            if (!Schema::hasColumn('client_leads', 'whatsapp_status')) {
                $table->string('whatsapp_status', 40)->default('pending_send')->after('consultation_time_slot');
            }
            if (!Schema::hasColumn('client_leads', 'whatsapp_sent_at')) {
                $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_status');
            }
            if (!Schema::hasColumn('client_leads', 'whatsapp_sent_by')) {
                $table->string('whatsapp_sent_by', 120)->nullable()->after('whatsapp_sent_at');
            }
            if (!Schema::hasColumn('client_leads', 'reminder_notes')) {
                $table->text('reminder_notes')->nullable()->after('whatsapp_sent_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('subscription_payments', 'receipt_number') ? 'receipt_number' : null,
                Schema::hasColumn('subscription_payments', 'plan_name_snapshot') ? 'plan_name_snapshot' : null,
                Schema::hasColumn('subscription_payments', 'billing_starts_at') ? 'billing_starts_at' : null,
                Schema::hasColumn('subscription_payments', 'billing_expires_at') ? 'billing_expires_at' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('client_leads', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('client_leads', 'scale') ? 'scale' : null,
                Schema::hasColumn('client_leads', 'subscription_plan_snapshot') ? 'subscription_plan_snapshot' : null,
                Schema::hasColumn('client_leads', 'consultation_time_slot') ? 'consultation_time_slot' : null,
                Schema::hasColumn('client_leads', 'whatsapp_status') ? 'whatsapp_status' : null,
                Schema::hasColumn('client_leads', 'whatsapp_sent_at') ? 'whatsapp_sent_at' : null,
                Schema::hasColumn('client_leads', 'whatsapp_sent_by') ? 'whatsapp_sent_by' : null,
                Schema::hasColumn('client_leads', 'reminder_notes') ? 'reminder_notes' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
