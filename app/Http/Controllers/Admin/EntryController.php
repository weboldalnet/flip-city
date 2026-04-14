<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\User;

class EntryController extends FlipCityAdminController
{
    public function scan(Request $request)
    {
        $token = $request->input('qr_code_token');
        $user = User::where('qr_code_token', $token)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Érvénytelen QR-kód']);
        }

        if (!$user->is_active || $user->is_blocked) {
            return response()->json(['success' => false, 'message' => 'Fiók letiltva vagy inaktív']);
        }

        if (!$user->card_registered && $user->balance <= 0) {
            return response()->json(['success' => false, 'message' => 'Nincs rögzített kártya vagy egyenleg']);
        }

        // Ellenőrizzük, hogy van-e már aktív belépése
        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();

        if ($activeEntry) {
            // Kiléptetés folyamata indulna, vagy jelzés
            return response()->json(['success' => true, 'action' => 'checkout', 'entry_id' => $activeEntry->id]);
        }

        // Beléptetés
        $entry = Entry::create([
            'user_id' => $user->id,
            'start_time' => now(),
            'rate' => config('flip-city.default_rate'),
            'guest_count' => $request->input('guest_count', 1),
        ]);

        return response()->json(['success' => true, 'message' => 'Sikeres belépés', 'entry' => $entry]);
    }

    public function checkout(Request $request, Entry $entry)
    {
        $entry->end_time = now();
        $durationHours = max(1, $entry->start_time->diffInHours($entry->end_time));
        $entry->total_cost = $durationHours * $entry->rate * $entry->guest_count;
        $entry->save();

        // Itt jönne a fizetési mód választása és számlázás
        return response()->json(['success' => true, 'total_cost' => $entry->total_cost]);
    }
}
