<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp')->nullable()->after('email_verified_at');
            $table->boolean('is_verified')->default(false)->after('otp');
            $table->string('referral_code')->nullable()->after('is_verified');
            $table->string('username')->unique()->nullable()->after('referral_code');
            $table->string('image')->nullable()->after('username');
            $table->boolean('is_profile_complete')->default(false)->after('image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
             $table->dropColumn([
                'otp', 'is_verified', 'referral_code',
                'username', 'image', 'is_profile_complete'
            ]);
        });
    }
};
