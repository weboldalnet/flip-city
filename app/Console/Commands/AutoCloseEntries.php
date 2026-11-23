<?php

namespace Weboldalnet\FlipCity\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Support\AutoClose;

class AutoCloseEntries extends Command
{
    protected $signature = 'flip-city:auto-close';
    protected $description = 'Automatikusan lezárja a kint felejtett belépéseket';

    public function handle()
    {
        $settings = AutoClose::settings();

        if (!$settings['enabled']) {
            $this->info('Az automatikus lezárás ki van kapcsolva.');
            return self::SUCCESS;
        }

        $now = now();
        $ids = Entry::whereNull('end_time')->orderBy('id')->pluck('id');

        foreach ($ids as $id) {
            $entry = DB::transaction(function () use ($id, $settings, $now) {
                $entry = Entry::whereKey($id)->lockForUpdate()->first();
                if (!$entry || $entry->end_time) {
                    return null;
                }

                $deadline = AutoClose::deadline($entry, $settings);
                if (!$deadline || $deadline['at']->gt($now)) {
                    return null;
                }

                $entry->end_time = $deadline['at'];
                $entry->is_auto_closed = true;
                $entry->is_failed = true;
                $entry->auto_close_reason = $deadline['reason'];
                $entry->total_cost = $entry->costBreakdown($entry->end_time)['total'];
                $entry->save();

                $user = $entry->user;
                if ($settings['block_user'] && $user && !$user->is_blocked) {
                    $user->is_blocked = true;
                    $user->blocked_at = $now;
                    $user->blocked_reason = AutoClose::blockReason($entry);
                    $user->save();
                }

                return $entry;
            });

            if ($entry) {
                $this->info("Automatikusan lezárva: Entry #{$entry->id} ({$entry->auto_close_reason}), " . AutoClose::money($entry->total_cost));
            }
        }

        return self::SUCCESS;
    }
}
