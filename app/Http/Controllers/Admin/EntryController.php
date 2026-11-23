<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\FlipCitySettings;
use Weboldalnet\FlipCity\Models\User;

class EntryController extends FlipCityAdminController
{
    public function index()
    {
        $entries = Entry::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        $flipCitySettings = [
            'default_rate' => FlipCitySettings::get('default_rate', config('flip-city.default_rate')),
            'companion_price' => FlipCitySettings::get('companion_price', config('flip-city.companion_price')),
        ];

        return view('flip-city::admin.flip-city.entries.index', compact('entries', 'flipCitySettings'));
    }

    private function countMessages(): array
    {
        return [
            'guest_count.min' => 'Legalább 1 játszót meg kell adni.',
            'children_count.lte' => 'A gyerekek száma nem lehet több a játszók számánál.',
            'children_count.min' => 'A gyerekek száma nem lehet negatív.',
            'companions_count.min' => 'A kísérők száma nem lehet negatív.',
        ];
    }

    public function storeFromBooking(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:flip_city_bookings,id',
            'guest_count' => 'required|integer|min:1',
            'children_count' => 'nullable|integer|min:0|lte:guest_count',
            'companions_count' => 'nullable|integer|min:0',
        ], $this->countMessages());

        if ($this->dayClosed()) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A mai nap le van zárva, beléptetés már nem lehetséges.');
        }

        $booking = \Weboldalnet\FlipCity\Models\Booking::findOrFail($validated['booking_id']);
        $user = $booking->user;

        if ($booking->status !== 'pending') {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'Ez a foglalás már be lett léptetve.');
        }

        if (!$user->is_active || $user->is_blocked) {
            return redirect()->back()->with('error', $this->unavailableMessage($user));
        }

        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();
        if ($activeEntry) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        if (!$this->createEntry($user, $validated)) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        $booking->update(['status' => 'confirmed']);

        return redirect()->route('flip-city.admin.dashboard')->with('success', 'Sikeres beléptetés foglalásból.');
    }

    public function scan(Request $request)
    {
        $token = $request->input('qr_code_token');
        $user = User::where('qr_code_token', $token)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Érvénytelen QR-kód']);
        }

        if (!$user->is_active || $user->is_blocked) {
            return response()->json(['success' => false, 'message' => $this->unavailableMessage($user)]);
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
        if ($this->dayClosed()) {
            return response()->json(['success' => false, 'message' => 'A mai nap le van zárva, beléptetés már nem lehetséges.']);
        }

        $booking = $user->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', 'pending')
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
            'notice'  => $user->checkinNotice(),
            'message' => $booking ? 'Találtunk mai foglalást!' : 'Nincs mai foglalás.',
        ]);
    }

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:flip_city_users,id',
            'guest_count' => 'required|integer|min:1',
            'children_count' => 'nullable|integer|min:0|lte:guest_count',
            'companions_count' => 'nullable|integer|min:0',
        ], $this->countMessages());

        if ($this->dayClosed()) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A mai nap le van zárva, beléptetés már nem lehetséges.');
        }

        $user = User::findOrFail($validated['user_id']);

        if (!$user->is_active || $user->is_blocked) {
            return redirect()->back()->with('error', $this->unavailableMessage($user));
        }

        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();
        if ($activeEntry) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        if (!$this->createEntry($user, $validated)) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        // Foglalás frissítése ha van
        $user->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);

        return redirect()->route('flip-city.admin.dashboard')->with('success', 'Sikeres manuális beléptetés.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'qr_code_token' => 'required|string',
            'guest_count' => 'required|integer|min:1',
            'children_count' => 'nullable|integer|min:0|lte:guest_count',
            'companions_count' => 'nullable|integer|min:0',
        ], $this->countMessages());

        if ($this->dayClosed()) {
            return response()->json(['success' => false, 'message' => 'A mai nap le van zárva, beléptetés már nem lehetséges.']);
        }

        $user = User::where('qr_code_token', $validated['qr_code_token'])->firstOrFail();

        if (!$user->is_active || $user->is_blocked) {
            return response()->json(['success' => false, 'message' => $this->unavailableMessage($user)]);
        }

        $entry = $this->createEntry($user, $validated);
        if (!$entry) {
            return response()->json(['success' => false, 'message' => 'A felhasználó már be van léptetve.']);
        }

        // Ha volt mai foglalása, jelöljük lezártnak (vagy igény szerint)
        $user->bookings()
            ->where('booking_date', now()->toDateString())
            ->where('status', 'pending')
            ->update(['status' => 'confirmed']);

        return response()->json([
            'success' => true,
            'message' => 'Sikeres belépés! Időszámláló elindítva.',
            'entry'   => $entry,
        ]);
    }

    private function unavailableMessage(User $user): string
    {
        if (!$user->is_blocked) {
            return 'A felhasználó fiókja inaktív.';
        }

        return $user->blocked_reason ? 'A fiók le van tiltva. ' . $user->blocked_reason : 'A fiók le van tiltva.';
    }

    private function dayClosed(): bool
    {
        return \Weboldalnet\FlipCity\Models\DailySummary::where('summary_date', date('Y-m-d'))->where('is_closed', true)->exists();
    }

    private function createEntry(User $user, array $validated): ?Entry
    {
        return DB::transaction(function () use ($user, $validated) {
            User::whereKey($user->id)->lockForUpdate()->first();

            if (Entry::where('user_id', $user->id)->whereNull('end_time')->exists()) {
                return null;
            }

            return Entry::create([
                'user_id'    => $user->id,
                'start_time' => now(),
                'rate'       => FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500)),
                'guest_count' => $validated['guest_count'],
                'children_count' => $validated['children_count'] ?? 0,
                'child_rate' => $this->childRate(),
                'companions_count' => $validated['companions_count'] ?? 0,
            ]);
        });
    }

    private function childRate(): float
    {
        $rate = FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500));

        return (float) FlipCitySettings::get('child_rate', config('flip-city.child_rate') ?? $rate);
    }

    public function checkout(Request $request, Entry $entry)
    {
        $isPartial = $request->boolean('partial');

        if ($isPartial) {
            $leaving = (int) $request->input('leaving_count', 1);
            $leavingChildren = min((int) $request->input('leaving_children', 0), $leaving, (int) $entry->children_count);
            if ($leaving - $leavingChildren > $entry->adultsCount()) {
                return response()->json(['success' => false, 'message' => 'Ennyi felnőtt nincs bent, növeld a távozó gyerekek számát.']);
            }
            $breakdown = $entry->costBreakdown(now(), $leaving - $leavingChildren, $leavingChildren, false);
            $guestCount = $leaving;
        } else {
            $breakdown = $entry->costBreakdown($entry->end_time ?: now());
            if ($entry->end_time && $entry->total_cost !== null) {
                $breakdown['total'] = (float) $entry->total_cost;
            }
            $guestCount = (int) $entry->guest_count;
        }

        return response()->json([
            'success' => true,
            'total_cost' => (float) $breakdown['total'],
            'base_cost' => (float) ($breakdown['adult_cost'] + $breakdown['child_cost']),
            'adults' => $breakdown['adults'],
            'adult_cost' => (float) $breakdown['adult_cost'],
            'children' => $breakdown['children'],
            'child_cost' => (float) $breakdown['child_cost'],
            'companions_cost' => (float) $breakdown['companions_cost'],
            'companions_count' => (int) $breakdown['companions'],
            'duration' => (int) $breakdown['minutes'],
            'billed_minutes' => (int) $breakdown['billed_minutes'],
            'min_applied' => (bool) $breakdown['min_applied'],
            'min_minutes' => (int) $breakdown['min_minutes'],
            'guest_count' => $guestCount,
        ]);
    }

    public function finalizeCheckout(Request $request, Entry $entry)
    {
        if ($entry->end_time) {
            return response()->json(['success' => false, 'message' => 'Ez a belépés már le van zárva.'], 422);
        }

        $entry->end_time = now();
        $entry->total_cost = $entry->costBreakdown($entry->end_time)['total'];
        $entry->save();

        return response()->json([
            'success'    => true,
            'total_cost' => $entry->total_cost,
        ]);
    }

    public function partialCheckout(Request $request, Entry $entry)
    {
        $leavingCount = (int) $request->input('leaving_count', 1);
        $leavingChildren = (int) $request->input('leaving_children', 0);
        $paymentMethod = $request->input('payment_method', 'cash');
        $cashReceived = (float) $request->input('cash_received', 0);

        if ($entry->end_time || $entry->is_failed) {
            return response()->json(['success' => false, 'message' => 'Ez a belépés már le van zárva.']);
        }

        if ($leavingCount < 1 || $leavingCount >= $entry->guest_count) {
            return response()->json(['success' => false, 'message' => 'Részleges kiléptetésnél legalább 1 főnek bent kell maradnia.']);
        }

        if ($leavingChildren < 0 || $leavingChildren > $leavingCount || $leavingChildren > $entry->children_count
            || $leavingCount - $leavingChildren > $entry->adultsCount()) {
            return response()->json(['success' => false, 'message' => 'A távozó gyerekek száma nem egyezik a bent lévőkkel.']);
        }

        $adultsLeaving = $leavingCount - $leavingChildren;
        $totalCostForLeaving = $entry->costBreakdown(now(), $adultsLeaving, $leavingChildren, false)['total'];
        $shownCost = $request->input('final_cost');
        if (is_numeric($shownCost)) {
            $earliest = $entry->costBreakdown(now()->subMinutes(5), $adultsLeaving, $leavingChildren, false)['total'];
            if ((float) $shownCost >= $earliest && (float) $shownCost <= $totalCostForLeaving) {
                $totalCostForLeaving = (float) $shownCost;
            }
        }

        $totalCostForLeaving = Entry::payableAmount((float) $totalCostForLeaving, $paymentMethod);

        $change = 0;
        if ($paymentMethod === 'cash') {
            if ($cashReceived < $totalCostForLeaving) {
                return response()->json(['success' => false, 'message' => 'A kapott összeg kevesebb a fizetendőnél!']);
            }
            $change = $cashReceived - $totalCostForLeaving;
        }

        $entry->guest_count -= $leavingCount;
        $entry->children_count -= $leavingChildren;
        $entry->save();

        $newInvoice = null;
        if (config('flip-city.billing_enabled', true)) {
            try {
                $invoiceResponse = \Weboldalnet\FlipCity\Services\InvoiceService::createInvoiceForEntry($entry, $totalCostForLeaving);

                if ($invoiceResponse && $invoiceResponse->isSuccess()) {
                    $newInvoice = \Weboldalnet\FlipCity\Models\Invoice::create([
                        'entry_id'       => $entry->id,
                        'user_id'        => $entry->user_id,
                        'amount'         => $totalCostForLeaving,
                        'payment_method' => $paymentMethod,
                        'cash_received'  => $cashReceived ?: null,
                        'change_given'   => $change,
                        'invoice_number' => $invoiceResponse->getDocumentNumber(),
                        'invoice_url'    => '',
                    ]);

                    $newInvoice->invoice_url = route('flip-city.admin.invoices.download', $newInvoice->id);
                    $newInvoice->save();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Számlázási hiba részleges kiléptetéskor: ' . $e->getMessage());
            }
        }

        if (!$newInvoice) {
            \Weboldalnet\FlipCity\Models\Invoice::create([
                'entry_id'       => $entry->id,
                'user_id'        => $entry->user_id,
                'amount'         => $totalCostForLeaving,
                'payment_method' => $paymentMethod,
                'cash_received'  => $cashReceived ?: null,
                'change_given'   => $change,
                'invoice_number' => null,
                'invoice_url'    => null,
            ]);
        }

        \Weboldalnet\FlipCity\Models\DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')])
            ->increment($paymentMethod === 'cash' ? 'total_cash' : 'total_card', $totalCostForLeaving);

        return response()->json([
            'success'          => true,
            'message'          => 'Részleges kiléptetés rögzítve',
            'total_cost'       => $totalCostForLeaving,
            'change'           => $change,
            'remaining_guests' => $entry->guest_count,
        ]);
    }

    public function fail(Entry $entry)
    {
        if ($entry->end_time && !$entry->is_failed) {
            return redirect()->back()->with('error', 'Ez a belépés már le van zárva.');
        }

        $entry->update([
            'end_time' => $entry->end_time ?: now(),
            'total_cost' => $entry->calculateCurrentCost(),
            'is_failed' => true
        ]);

        return redirect()->back()->with('success', 'A belépés meghiúsulttá lett nyilvánítva.');
    }

    public function unfail(Entry $entry)
    {
        if (!$entry->is_failed) {
            return redirect()->back()->with('error', 'Ez a belépés nem meghiúsult.');
        }

        // Fizetési adatok (alapértelmezett KP, mert admin felületen történik a visszaállítás)
        $paymentMethod = 'cash';
        $totalCost = $entry->total_cost ?: $entry->calculateCurrentCost();
        $paidAmount = Entry::payableAmount((float) $totalCost, $paymentMethod);

        $newInvoice = null;
        if (config('flip-city.billing_enabled', true)) {
            try {
                $invoiceResponse = \Weboldalnet\FlipCity\Services\InvoiceService::createInvoiceForEntry($entry, $paidAmount);

                if ($invoiceResponse && $invoiceResponse->isSuccess()) {
                    $newInvoice = \Weboldalnet\FlipCity\Models\Invoice::create([
                        'entry_id'       => $entry->id,
                        'user_id'        => $entry->user_id,
                        'amount'         => $paidAmount,
                        'payment_method' => $paymentMethod,
                        'invoice_number' => $invoiceResponse->getDocumentNumber(),
                        'invoice_url'    => '',
                    ]);

                    $newInvoice->invoice_url = route('flip-city.admin.invoices.download', $newInvoice->id);
                    $newInvoice->save();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Számlázási hiba meghiúsult belépés visszaállításakor: ' . $e->getMessage());
            }
        }

        if (!$newInvoice) {
            \Weboldalnet\FlipCity\Models\Invoice::create([
                'entry_id'       => $entry->id,
                'user_id'        => $entry->user_id,
                'amount'         => $paidAmount,
                'payment_method' => $paymentMethod,
                'invoice_number' => null,
                'invoice_url'    => null,
            ]);
        }

        \Weboldalnet\FlipCity\Models\DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')])
            ->increment('total_cash', $paidAmount);

        $entry->update([
            'is_failed' => false,
            'total_cost' => $totalCost
        ]);

        $message = 'A belépés sikeresen visszaállítva és a kasszához adva.';
        if ($entry->is_auto_closed && $entry->user && $entry->user->is_blocked) {
            $message .= ' Az ügyfél tiltása megmaradt, az adatlapján oldható fel.';
        }

        return redirect()->back()->with('success', $message);
    }
}
