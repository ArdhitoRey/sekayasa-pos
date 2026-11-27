<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nik', 20)->nullable()->unique()->after('phone');
            $table->string('job_title', 100)->nullable()->after('nik');
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->string('legal_type', 50)->nullable()->after('name');
            $table->string('business_category', 100)->nullable()->after('legal_type');
            $table->string('npwp', 30)->nullable()->after('phone');
            $table->string('nib', 30)->nullable()->after('npwp');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 10)->nullable()->after('province');
            $table->string('outlet_count_estimate', 50)->nullable()->after('postal_code');
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->string('outlet_code', 30)->nullable()->after('name');
            $table->string('city', 100)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn(['nik', 'job_title']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'legal_type',
                'business_category',
                'npwp',
                'nib',
                'city',
                'province',
                'postal_code',
                'outlet_count_estimate',
            ]);
        });

        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['outlet_code', 'city']);
        });
    }
};
