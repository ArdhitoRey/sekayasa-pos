<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->after('business_id')->constrained('outlets')->nullOnDelete();
            $table->string('phone')->nullable()->after('email');
            $table->enum('role', ['superadmin', 'owner', 'admin', 'cashier'])->default('owner')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropForeign(['outlet_id']);
            $table->dropColumn(['business_id', 'outlet_id', 'phone', 'role', 'is_active']);
        });

        Schema::dropIfExists('outlets');
    }
};
