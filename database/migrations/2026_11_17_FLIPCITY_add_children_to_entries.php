<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flip_city_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('flip_city_entries', 'children_count')) {
                $table->integer('children_count')->default(0);
            }
            if (!Schema::hasColumn('flip_city_entries', 'child_rate')) {
                $table->decimal('child_rate', 10, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('flip_city_entries', function (Blueprint $table) {
            if (Schema::hasColumn('flip_city_entries', 'child_rate')) {
                $table->dropColumn('child_rate');
            }
            if (Schema::hasColumn('flip_city_entries', 'children_count')) {
                $table->dropColumn('children_count');
            }
        });
    }
};
