<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->json('feature_settings')->nullable()->after('service_type');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->string('receipt_header')->nullable()->after('phone');
            $table->string('receipt_footer')->nullable()->after('receipt_header');
            $table->decimal('tax_percentage', 5, 2)->nullable()->after('receipt_footer');
            $table->json('feature_overrides')->nullable()->after('tax_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('feature_settings');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['receipt_header', 'receipt_footer', 'tax_percentage', 'feature_overrides']);
        });
    }
};
