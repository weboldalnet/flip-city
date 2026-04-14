<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            CREATE TABLE flip_city_users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE,
                phone VARCHAR(50),
                password VARCHAR(255),
                billing_details TEXT,
                terms_accepted BOOLEAN NOT NULL DEFAULT FALSE,
                qr_code_token VARCHAR(255) UNIQUE,
                qr_code_svg TEXT,
                is_active BOOLEAN NOT NULL DEFAULT FALSE,
                is_blocked BOOLEAN NOT NULL DEFAULT FALSE,
                activation_token VARCHAR(255),
                activated_at TIMESTAMP WITHOUT TIME ZONE,
                balance DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                card_registered BOOLEAN NOT NULL DEFAULT FALSE,
                remember_token VARCHAR(100),
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE flip_city_entries (
                id SERIAL PRIMARY KEY,
                user_id INTEGER REFERENCES flip_city_users(id) ON DELETE CASCADE,
                start_time TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                end_time TIMESTAMP WITHOUT TIME ZONE,
                rate DECIMAL(10, 2) NOT NULL,
                guest_count INTEGER NOT NULL DEFAULT 1,
                is_auto_closed BOOLEAN NOT NULL DEFAULT FALSE,
                total_cost DECIMAL(10, 2),
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE flip_city_bookings (
                id SERIAL PRIMARY KEY,
                user_id INTEGER REFERENCES flip_city_users(id) ON DELETE CASCADE,
                booking_date DATE NOT NULL,
                booking_time TIME WITHOUT TIME ZONE NOT NULL,
                guest_count INTEGER NOT NULL,
                qr_code_token VARCHAR(255) UNIQUE,
                status VARCHAR(50) DEFAULT 'pending',
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE flip_city_invoices (
                id SERIAL PRIMARY KEY,
                entry_id INTEGER REFERENCES flip_city_entries(id) ON DELETE SET NULL,
                user_id INTEGER REFERENCES flip_city_users(id) ON DELETE SET NULL,
                amount DECIMAL(10, 2) NOT NULL,
                payment_method VARCHAR(50) NOT NULL, -- 'cash', 'card', 'auto'
                cash_received DECIMAL(10, 2),
                change_given DECIMAL(10, 2),
                invoice_number VARCHAR(100) UNIQUE,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE flip_city_daily_summaries (
                id SERIAL PRIMARY KEY,
                summary_date DATE UNIQUE NOT NULL,
                total_cash DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                total_card DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                total_auto DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                is_closed BOOLEAN NOT NULL DEFAULT FALSE,
                closed_at TIMESTAMP WITHOUT TIME ZONE,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");

        DB::statement("
            CREATE TABLE password_reset_tokens (
                email VARCHAR(255) PRIMARY KEY,
                token VARCHAR(255) NOT NULL,
                created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            );
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS password_reset_tokens;");
        DB::statement("DROP TABLE IF EXISTS flip_city_daily_summaries;");
        DB::statement("DROP TABLE IF EXISTS flip_city_invoices;");
        DB::statement("DROP TABLE IF EXISTS flip_city_bookings;");
        DB::statement("DROP TABLE IF EXISTS flip_city_entries;");
        DB::statement("DROP TABLE IF EXISTS flip_city_users;");
    }
};
