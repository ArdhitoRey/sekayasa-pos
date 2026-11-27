<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('subscription_payments', 'duration_months')) {
                $table->unsignedSmallInteger('duration_months')->default(1)->after('plan_name_snapshot');
            }
            if (!Schema::hasColumn('subscription_payments', 'duration_days')) {
                $table->unsignedSmallInteger('duration_days')->default(30)->after('duration_months');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_payments', function (Blueprint $table) {
            $cols = array_filter([
                Schema::hasColumn('subscription_payments', 'duration_months') ? 'duration_months' : null,
                Schema::hasColumn('subscription_payments', 'duration_days') ? 'duration_days' : null,
            ]);
            if ($cols) {
                $table->dropColumn($cols);
            }
        });
    }
};
