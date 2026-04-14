<?php

namespace Weboldalnet\FlipCity\Console\Commands;

use Illuminate\Console\Command;
use Weboldalnet\FlipCity\Models\Entry;

class AutoCloseEntries extends Command
{
    protected $signature = 'flip-city:auto-close';
    protected $description = 'Automatikusan lezárja a kint felejtett belépéseket';

    public function handle()
    {
        $limitHours = config('flip-city.auto_close_hours', 3);
        $entries = Entry::whereNull('end_time')
            ->where('start_time', '<=', now()->subHours($limitHours))
            ->get();

        foreach ($entries as $entry) {
            $entry->end_time = $entry->start_time->addHours($limitHours);
            $entry->is_auto_closed = true;
            $entry->total_cost = $limitHours * $entry->rate * $entry->guest_count;
            $entry->save();

            $this->info("Automatikusan lezárva: Entry #{$entry->id}");
        }
    }
}
