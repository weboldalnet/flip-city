<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flip_city_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('flip_city_entries', 'auto_close_reason')) {
                $table->string('auto_close_reason')->nullable();
            }
        });

        Schema::table('flip_city_users', function (Blueprint $table) {
            if (!Schema::hasColumn('flip_city_users', 'blocked_reason')) {
                $table->text('blocked_reason')->nullable();
            }
            if (!Schema::hasColumn('flip_city_users', 'blocked_at')) {
                $table->timestamp('blocked_at')->nullable();
            }
        });

        $defaults = [
            'auto_close_enabled' => '1',
            'auto_close_hours' => (string) config('flip-city.auto_close_hours', 3),
            'auto_close_time' => '',
            'auto_close_block_user' => '1',
            'auto_close_warn_on_checkin' => '1',
        ];

        foreach ($defaults as $key => $value) {
            if (!DB::table('flip_city_settings')->where('key', $key)->exists()) {
                DB::table('flip_city_settings')->insert(['key' => $key, 'value' => $value]);
            }
            Cache::forget('flip_city_setting_v2_' . $key);
        }
    }

    public function down(): void
    {
        Schema::table('flip_city_users', function (Blueprint $table) {
            if (Schema::hasColumn('flip_city_users', 'blocked_at')) {
                $table->dropColumn('blocked_at');
            }
            if (Schema::hasColumn('flip_city_users', 'blocked_reason')) {
                $table->dropColumn('blocked_reason');
            }
        });

        Schema::table('flip_city_entries', function (Blueprint $table) {
            if (Schema::hasColumn('flip_city_entries', 'auto_close_reason')) {
                $table->dropColumn('auto_close_reason');
            }
        });
    }
};
