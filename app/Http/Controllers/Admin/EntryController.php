<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
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

    public function storeFromBooking(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:flip_city_bookings,id',
            'guest_count' => 'required|integer|min:1',
            'companions_count' => 'nullable|integer|min:0',
        ]);

        $booking = \Weboldalnet\FlipCity\Models\Booking::findOrFail($validated['booking_id']);
        $user = $booking->user;

        if (!$user->is_active || $user->is_blocked) {
            return redirect()->back()->with('error', 'A felhasználó inaktív vagy le van tiltva.');
        }

        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();
        if ($activeEntry) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        Entry::create([
            'user_id'    => $user->id,
            'start_time' => now(),
            'rate'       => FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500)),
            'guest_count' => $validated['guest_count'],
            'companions_count' => $validated['companions_count'] ?? 0,
        ]);

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

    public function storeManual(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:flip_city_users,id',
            'guest_count' => 'required|integer|min:1',
            'companions_count' => 'nullable|integer|min:0',
        ]);

        $user = User::findOrFail($validated['user_id']);

        if (!$user->is_active || $user->is_blocked) {
            return redirect()->back()->with('error', 'A felhasználó inaktív vagy le van tiltva.');
        }

        $activeEntry = Entry::where('user_id', $user->id)->whereNull('end_time')->first();
        if ($activeEntry) {
            return redirect()->route('flip-city.admin.dashboard')->with('error', 'A felhasználó már be van léptetve.');
        }

        Entry::create([
            'user_id'    => $user->id,
            'start_time' => now(),
            'rate'       => FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500)),
            'guest_count' => $validated['guest_count'],
            'companions_count' => $validated['companions_count'] ?? 0,
        ]);

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
            'companions_count' => 'nullable|integer|min:0',
        ]);

        $user = User::where('qr_code_token', $validated['qr_code_token'])->firstOrFail();

        $entry = Entry::create([
            'user_id'    => $user->id,
            'start_time' => now(),
            'rate'       => FlipCitySettings::get('default_rate', config('flip-city.default_rate', 1500)),
            'guest_count' => $validated['guest_count'],
            'companions_count' => $validated['companions_count'] ?? 0,
        ]);

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

    public function checkout(Request $request, Entry $entry)
    {
        $leavingCount = (int) $request->input('leaving_count', $entry->guest_count);
        $isPartial = $request->has('partial') && $request->input('partial') == true;

        $companionPrice = (float) FlipCitySettings::get('companion_price', config('flip-city.companion_price', 500));
        $companionsCount = ($isPartial) ? 0 : (int)$entry->companions_count;
        $companionsCost = $companionsCount * $companionPrice;

        if ($entry->end_time && !$isPartial) {
            $cost = (float)$entry->total_cost;
            $durationMinutes = $entry->start_time->diffInMinutes($entry->end_time);
            if ($durationMinutes < 1) $durationMinutes = 1;
            $baseCost = $cost - $companionsCost;
        } else {
            $diffInSeconds = $entry->start_time->diffInSeconds(now());
            $durationMinutes = ceil($diffInSeconds / 60);
            if ($durationMinutes < 1) $durationMinutes = 1;
            
            $baseCost = round(($durationMinutes / 60) * $entry->rate * $leavingCount);
            $cost = $baseCost + $companionsCost;
        }

        return response()->json([
            'success'    => true,
            'total_cost' => (float)$cost,
            'base_cost'  => (float)$baseCost,
            'companions_cost' => (float)$companionsCost,
            'companions_count' => (int)$companionsCount,
            'duration'   => (int)$durationMinutes,
            'guest_count' => (int)$leavingCount,
        ]);
    }

    public function finalizeCheckout(Request $request, Entry $entry)
    {
        $entry->end_time = now();
        $diffInSeconds = $entry->start_time->diffInSeconds($entry->end_time);
        $durationMinutes = ceil($diffInSeconds / 60);
        if ($durationMinutes < 1) $durationMinutes = 1;

        $companionPrice = (float) FlipCitySettings::get('companion_price', config('flip-city.companion_price', 500));
        $companionsCost = $entry->companions_count * $companionPrice;

        $entry->total_cost = round(($durationMinutes / 60) * $entry->rate * $entry->guest_count) + $companionsCost;
        $entry->save();

        return response()->json([
            'success'    => true,
            'total_cost' => $entry->total_cost,
        ]);
    }

    public function partialCheckout(Request $request, Entry $entry)
    {
        $leavingCount = (int) $request->input('leaving_count', 1);
        $paymentMethod = $request->input('payment_method', 'cash');
        $cashReceived = (float) $request->input('cash_received', 0);

        if ($leavingCount >= $entry->guest_count) {
            // Ez ne történjen meg a JS validáció miatt, de kezeljük
            return response()->json(['success' => false, 'message' => 'Részleges kiléptetésnél legalább 1 főnek bent kell maradnia.']);
        }

        $now = now();
        $diffInSeconds = $entry->start_time->diffInSeconds($now);
        $durationMinutes = ceil($diffInSeconds / 60);
        if ($durationMinutes < 1) $durationMinutes = 1;

        $costPerPerson = ($durationMinutes / 60) * $entry->rate;
        $totalCostForLeaving = round($costPerPerson * $leavingCount);

        $change = 0;
        if ($paymentMethod === 'cash') {
            if ($cashReceived < $totalCostForLeaving) {
                return response()->json(['success' => false, 'message' => 'A kapott összeg kevesebb a fizetendőnél!']);
            }
            $change = $cashReceived - $totalCostForLeaving;
        }

        $entry->guest_count -= $leavingCount;
        $entry->save();

        // Számlázás integrálása részleges kiléptetésnél
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

        // Napi összesítő frissítése
        $summary = \Weboldalnet\FlipCity\Models\DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')]);
        if ($paymentMethod === 'cash') {
            $summary->total_cash += $totalCostForLeaving;
        } else {
            $summary->total_card += $totalCostForLeaving;
        }
        $summary->save();

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

        // Számlázás integrálása
        if (config('flip-city.billing_enabled', true)) {
            try {
                $invoiceResponse = \Weboldalnet\FlipCity\Services\InvoiceService::createInvoiceForEntry($entry, $totalCost);

                if ($invoiceResponse && $invoiceResponse->isSuccess()) {
                    $newInvoice = \Weboldalnet\FlipCity\Models\Invoice::create([
                        'entry_id'       => $entry->id,
                        'user_id'        => $entry->user_id,
                        'amount'         => $totalCost,
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
        } else {
            // Számlázás nélkül is rögzítjük a tranzakciót
            \Weboldalnet\FlipCity\Models\Invoice::create([
                'entry_id'       => $entry->id,
                'user_id'        => $entry->user_id,
                'amount'         => $totalCost,
                'payment_method' => $paymentMethod,
                'invoice_number' => 'N/A',
                'invoice_url'    => '',
            ]);
        }

        // Napi összesítő frissítése
        $summary = \Weboldalnet\FlipCity\Models\DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')]);
        if ($paymentMethod === 'cash') {
            $summary->total_cash += $totalCost;
        } else {
            $summary->total_card += $totalCost;
        }
        $summary->save();

        $entry->update([
            'is_failed' => false,
            'total_cost' => $totalCost
        ]);

        return redirect()->back()->with('success', 'A belépés sikeresen visszaállítva és a kasszához adva.');
    }
}
