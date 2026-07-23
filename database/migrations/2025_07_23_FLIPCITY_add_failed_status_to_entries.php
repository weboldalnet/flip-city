<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE flip_city_entries ADD COLUMN is_failed BOOLEAN NOT NULL DEFAULT FALSE");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE flip_city_entries DROP COLUMN IF EXISTS is_failed");
    }
};
