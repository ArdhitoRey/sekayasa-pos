<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->foreignId('plan_id')->nullable()->after('subscription_id')->constrained('plans')->nullOnDelete();
            $table->string('invoice_number')->nullable()->unique()->after('plan_id');
            $table->decimal('base_amount', 12, 2)->default(0)->after('invoice_number');
            $table->integer('unique_code')->default(0)->after('base_amount');
            $table->text('qris_payload')->nullable()->after('payment_method');
            $table->json('webhook_payload')->nullable()->after('notes');
            $table->timestamp('expires_at')->nullable()->after('verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn([
                'plan_id',
                'invoice_number',
                'base_amount',
                'unique_code',
                'qris_payload',
                'webhook_payload',
                'expires_at',
            ]);
        });
    }
};
