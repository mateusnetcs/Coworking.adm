<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('contact_email')->nullable()->after('activity');
            $table->string('phone', 30)->nullable()->after('contact_email');
            $table->string('institution', 120)->nullable()->after('phone');
            $table->string('space_type', 20)->nullable()->after('institution');
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('computers')->nullable()->after('space_type');
            } else {
                $table->json('computers')->nullable()->after('space_type');
            }
            $table->timestampTz('terms_accepted_at')->nullable()->after('computers');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'contact_email',
                'phone',
                'institution',
                'space_type',
                'computers',
                'terms_accepted_at',
            ]);
        });
    }
};
