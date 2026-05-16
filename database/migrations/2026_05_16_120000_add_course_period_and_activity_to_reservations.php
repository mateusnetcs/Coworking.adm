<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('reservations', 'course_period')) {
                $table->unsignedTinyInteger('course_period')->default(1)->after('ends_at');
            }
            if (! Schema::hasColumn('reservations', 'activity')) {
                $table->text('activity')->nullable()->after('course_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $columns = array_filter(
                ['course_period', 'activity'],
                static fn (string $column): bool => Schema::hasColumn('reservations', $column),
            );

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
