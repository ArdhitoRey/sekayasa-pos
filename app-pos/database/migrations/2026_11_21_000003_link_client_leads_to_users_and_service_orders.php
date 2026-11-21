<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('service_type', 30)->default('none')->after('is_active'); // none, template_saas, custom_pos
        });

        Schema::table('client_leads', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->foreignId('business_id')->nullable()->after('user_id')->constrained('businesses')->nullOnDelete();
            $table->text('template_gap_notes')->nullable()->after('notes');
            $table->dateTime('preferred_consultation_at')->nullable()->after('template_gap_notes');
        });
    }

    public function down(): void
    {
        Schema::table('client_leads', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['business_id']);
            $table->dropColumn(['user_id', 'business_id', 'template_gap_notes', 'preferred_consultation_at']);
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('service_type');
        });
    }
};
