<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Settings table
        DB::statement("
            CREATE TABLE flip_city_settings (
                id SERIAL PRIMARY KEY,
                key VARCHAR(255) UNIQUE NOT NULL,
                value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Initial settings
        DB::table('flip_city_settings')->insert([
            ['key' => 'default_rate', 'value' => '1500'],
            ['key' => 'companion_price', 'value' => '500'],
            ['key' => 'profile_qr_print_text', 'value' => 'Kérjük, mutassa be ezt a kódot a belépéshez!'],
            ['key' => 'show_profile_booking', 'value' => '1'],
        ]);

        // Add companions_count to entries
        DB::statement("ALTER TABLE flip_city_entries ADD COLUMN companions_count INTEGER DEFAULT 0");
    }

    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS flip_city_settings");
        DB::statement("ALTER TABLE flip_city_entries DROP COLUMN IF EXISTS companions_count");
    }
};
