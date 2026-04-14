<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\User;

class EntryController extends FlipCityAdminController
{
    public function index()
    {
        $entries = Entry::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('flip-city::admin.flip-city.entries.index', compact('entries'));
    }

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

        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();

        if ($activeEntry) {
            return response()->json([
                'success'  => true,
                'action'   => 'checkout',
                'entry_id' => $activeEntry->id,
                'message'  => 'Vendég már bent van, kiléptetés előkészítve',
            ]);
        }

        // Foglalás ellenőrzése
        $booking = $user->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', '!=', 'completed')
            ->orderBy('booking_time')
            ->first();

        return response()->json([
            'success' => true,
            'action'  => 'confirm_checkin',
            'user'    => [
                'id' => $user->id,
                'name' => $user->name,
                'qr_code_token' => $user->qr_code_token,
                'card_registered' => $user->card_registered,
                'balance' => $user->balance,
            ],
            'booking' => $booking,
            'message' => $booking ? 'Találtunk mai foglalást!' : 'Nincs mai foglalás.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'qr_code_token' => 'required|string',
            'guest_count' => 'required|integer|min:1',
        ]);

        $user = User::where('qr_code_token', $validated['qr_code_token'])->firstOrFail();

        if (!$user->card_registered && $user->balance <= 0) {
            return response()->json(['success' => false, 'message' => 'Nincs rögzített bankkártya vagy egyenleg']);
        }

        $entry = Entry::create([
            'user_id'    => $user->id,
            'start_time' => now(),
            'rate'       => config('flip-city.default_rate', 1500),
            'guest_count' => $validated['guest_count'],
        ]);

        // Ha volt mai foglalása, jelöljük lezártnak (vagy igény szerint)
        $user->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', '!=', 'completed')
            ->update(['status' => 'confirmed']);

        return response()->json([
            'success' => true,
            'message' => 'Sikeres belépés! Időszámláló elindítva.',
            'entry'   => $entry,
        ]);
    }

    public function checkout(Request $request, Entry $entry)
    {
        if ($entry->end_time) {
            $cost = $entry->total_cost;
        } else {
            $durationMinutes = max(1, $entry->start_time->diffInMinutes(now()));
            $cost = round(($durationMinutes / 60) * $entry->rate * $entry->guest_count);
        }

        return response()->json([
            'success'    => true,
            'total_cost' => $cost,
            'duration'   => $entry->start_time->diffInMinutes(now()),
            'guest_count' => $entry->guest_count,
        ]);
    }

    public function finalizeCheckout(Request $request, Entry $entry)
    {
        $entry->end_time = now();
        $durationMinutes = max(1, $entry->start_time->diffInMinutes($entry->end_time));
        $entry->total_cost = round(($durationMinutes / 60) * $entry->rate * $entry->guest_count);
        $entry->save();

        return response()->json([
            'success'    => true,
            'total_cost' => $entry->total_cost,
        ]);
    }

    public function partialCheckout(Request $request, Entry $entry)
    {
        $leavingCount = (int) $request->input('leaving_count', 1);

        if ($leavingCount >= $entry->guest_count) {
            return $this->finalizeCheckout($request, $entry);
        }

        $now = now();
        $durationMinutes = max(1, $entry->start_time->diffInMinutes($now));
        $costPerPerson = ($durationMinutes / 60) * $entry->rate;
        $totalCostForLeaving = round($costPerPerson * $leavingCount);

        $entry->guest_count -= $leavingCount;
        $entry->save();

        return response()->json([
            'success'          => true,
            'message'          => 'Részleges kiléptetés rögzítve',
            'total_cost'       => $totalCostForLeaving,
            'remaining_guests' => $entry->guest_count,
        ]);
    }
}
