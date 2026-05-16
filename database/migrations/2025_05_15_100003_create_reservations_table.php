<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            if (Schema::getConnection()->getDriverName() === 'pgsql') {
                $table->jsonb('companions');
            } else {
                $table->json('companions');
            }
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement("
                ALTER TABLE reservations
                ADD CONSTRAINT reservations_no_overlap
                EXCLUDE USING gist (
                    tstzrange(starts_at, ends_at, '[)') WITH &&
                )
            ");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reservations DROP CONSTRAINT IF EXISTS reservations_no_overlap');
        }

        Schema::dropIfExists('reservations');
    }
};
