<?php

namespace Weboldalnet\FlipCity\Support;

use Carbon\Carbon;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\FlipCitySettings;
use Weboldalnet\FlipCity\Models\User;

class AutoClose
{
    public static function settings(): array
    {
        $time = (string) FlipCitySettings::get('auto_close_time', config('flip-city.auto_close_time', ''));

        return [
            'enabled' => (bool) FlipCitySettings::get('auto_close_enabled', config('flip-city.auto_close_enabled', true)),
            'hours' => max(0, (float) FlipCitySettings::get('auto_close_hours', config('flip-city.auto_close_hours', 3))),
            'time' => preg_match('/^\d{2}:\d{2}$/', $time) ? $time : '',
            'block_user' => (bool) FlipCitySettings::get('auto_close_block_user', config('flip-city.auto_close_block_user', true)),
            'warn_on_checkin' => (bool) FlipCitySettings::get('auto_close_warn_on_checkin', config('flip-city.auto_close_warn_on_checkin', true)),
        ];
    }

    /**
     * The earliest moment the entry has to be closed, with the human readable reason.
     */
    public static function deadline(Entry $entry, ?array $settings = null): ?array
    {
        $settings = $settings ?? self::settings();
        $candidates = [];

        if ($settings['hours'] > 0) {
            $candidates[] = [
                'at' => $entry->start_time->copy()->addMinutes((int) round($settings['hours'] * 60)),
                'reason' => self::formatHours($settings['hours']) . ' óra bent tartózkodás után',
            ];
        }

        if ($settings['time'] !== '') {
            [$hour, $minute] = array_map('intval', explode(':', $settings['time']));
            $at = $entry->start_time->copy()->setTime($hour, $minute);
            if ($at->lte($entry->start_time)) {
                $at->addDay();
            }
            $candidates[] = [
                'at' => $at,
                'reason' => 'napi zárási időpont (' . $settings['time'] . ')',
            ];
        }

        if (!$candidates) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a['at']->getTimestamp() <=> $b['at']->getTimestamp());

        return $candidates[0];
    }

    public static function blockReason(Entry $entry): string
    {
        return 'Automatikus lezárás: a ' . $entry->start_time->format('Y.m.d. H:i') . ' belépés nem lett kiléptetve, a rendszer '
            . $entry->end_time->format('Y.m.d. H:i') . '-kor zárta le (' . $entry->auto_close_reason . '). Rendezetlen: '
            . self::money($entry->total_cost) . '.';
    }

    /**
     * Short, non-intrusive check-in hint when the previous visit was closed by the system.
     */
    public static function checkinNotice(User $user): ?string
    {
        if (!self::settings()['warn_on_checkin']) {
            return null;
        }

        $last = $user->lastEntry;
        $unpaid = (float) ($user->unpaid_auto_closed_total ?? $user->unpaidAutoClosedEntries()->sum('total_cost'));

        $parts = [];
        if ($last && $last->end_time && $last->is_auto_closed) {
            $parts[] = 'Előző alkalommal (' . $last->start_time->format('Y.m.d.') . ') nem lett kiléptetve, a rendszer zárta le.';
            if (!$last->is_failed) {
                $parts[] = 'Azóta rendezve.';
            }
        }

        if ($unpaid > 0) {
            $parts[] = 'Rendezetlen: ' . self::money($unpaid) . '.';
        }

        return $parts ? implode(' ', $parts) : null;
    }

    public static function money($amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' Ft';
    }

    private static function formatHours(float $hours): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format($hours, 2, '.', ''), '0'), '.'));
    }
}
