<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Weboldalnet\FlipCity\Mail\FlipCityMail;
use Weboldalnet\FlipCity\Models\Booking;
use Weboldalnet\FlipCity\Models\DailySummary;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\Invoice;
use Weboldalnet\FlipCity\Models\FlipCitySettings;
use Weboldalnet\FlipCity\Models\User;
use Weboldalnet\FlipCity\Services\QRCodeService;

class DashboardController extends FlipCityAdminController
{
    public function index()
    {
        $activeEntries = Entry::with('user')->whereNull('end_time')->get();
        $todaySummary = DailySummary::where('summary_date', date('Y-m-d'))->first();

        $todayBookings = Booking::with('user')
            ->where('booking_date', date('Y-m-d'))
            ->where('status', 'pending')
            ->orderBy('booking_time', 'asc')
            ->get();

        $futureBookings = Booking::with('user')
            ->where('booking_date', '>', date('Y-m-d'))
            ->orderBy('booking_date', 'asc')
            ->orderBy('booking_time', 'asc')
            ->get();

        $failedEntries = Entry::with('user')
            ->where('is_failed', true)
            ->whereHas('user', function($query) {
                $query->where('is_blocked', false);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('flip-city::admin.flip-city.dashboard', compact('activeEntries', 'todaySummary', 'todayBookings', 'futureBookings', 'failedEntries'));
    }

    public function closeDay()
    {
        $today = date('Y-m-d');
        $summary = DailySummary::firstOrCreate(['summary_date' => $today]);

        $invoices = Invoice::whereDate('created_at', $today)->get();

        $summary->total_cash = $invoices->where('payment_method', 'cash')->sum('amount');
        $summary->total_card = $invoices->where('payment_method', 'card')->sum('amount');
        $summary->total_auto = $invoices->where('payment_method', 'auto')->sum('amount');
        $summary->is_closed = true;
        $summary->closed_at = now();
        $summary->save();

        return redirect()->back()->with('success', 'A nap sikeresen lezárva.');
    }

    public function addUser(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'billing_zip' => 'required|string|max:10',
            'billing_city' => 'required|string|max:255',
            'billing_address' => 'required|string|max:255',
        ]);

        $user = null;

        // Keresés meglévő account alapján
        if ($validated['email']) {
            $user = User::where('email', $validated['email'])->first();
        }

        if (!$user && $validated['phone']) {
            // Telefonszám normalizálás (csak számok)
            $phoneDigits = preg_replace('/[^0-9]/', '', $validated['phone']);
            if ($phoneDigits) {
                $user = User::whereRaw("regexp_replace(phone, '[^0-9]', '', 'g') = ?", [$phoneDigits])->first();
            }
        }

        if ($user) {
            // Meglévő account frissítése, ha hiányzik adat
            $updateData = [];
            if (!$user->phone && $validated['phone']) $updateData['phone'] = $validated['phone'];
            if (!$user->billing_zip) $updateData['billing_zip'] = $validated['billing_zip'];
            if (!$user->billing_city) $updateData['billing_city'] = $validated['billing_city'];
            if (!$user->billing_address) $updateData['billing_address'] = $validated['billing_address'];

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Ha van a kérésben visszairányítási URL szándék, vagy maradjunk a konzisztenciánál
            return redirect()->route('flip-city.admin.users.show', $user)
                ->with('success', 'Meglévő ügyfél azonosítva és frissítve.');
        }

        // Új ügyfél létrehozása
        $qrToken = Str::uuid()->toString();
        $qrSvg = QRCodeService::generateQRCode($qrToken);

        $user = User::create([
            'name'           => $validated['name'],
            'email'          => $validated['email'] ?? null,
            'phone'          => $validated['phone'] ?? null,
            'billing_zip'    => $validated['billing_zip'],
            'billing_city'   => $validated['billing_city'],
            'billing_address' => $validated['billing_address'],
            'qr_code_token'  => $qrToken,
            'qr_code_svg'    => $qrSvg,
            'is_active'      => true,
            'terms_accepted' => true,
        ]);

        if ($user->email) {
            $token = Str::random(64);
            \Illuminate\Support\Facades\DB::table("password_reset_tokens")->updateOrInsert(
                ["email" => $user->email],
                [
                    "email" => $user->email,
                    "token" => $token,
                    "created_at" => now()
                ]
            );

            $mailData = [
                'subject'     => 'Regisztráció - Jelszó beállítása',
                'success_res' => 'Sikeresen regisztráltunk a rendszerbe!',
                'desc'        => 'Kérjük, állítsa be jelszavát az alábbi linken: <br><br>' .
                                 '<a href="' . route('flip-city.password.setup', $token) . '" style="background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Jelszó beállítása</a>',
            ];
            Mail::to($user->email)->send(new FlipCityMail($user, $mailData));
        }

        return redirect()->route('flip-city.admin.users.show', $user)
            ->with('success', 'Ügyfél sikeresen hozzáadva.');
    }

    public function settings()
    {
        return view('flip-city::admin.flip-city.settings');
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'default_rate' => 'required|numeric|min:0',
            'companion_price' => 'required|numeric|min:0',
            'profile_qr_print_text' => 'nullable|string',
            'show_profile_booking' => 'required|boolean',
        ]);

        foreach ($validated as $key => $value) {
            FlipCitySettings::set($key, $value);
        }

        return redirect()->back()->with('success', 'Beállítások sikeresen mentve.');
    }
}
