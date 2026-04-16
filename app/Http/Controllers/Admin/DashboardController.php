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

        return view('flip-city::admin.flip-city.dashboard', compact('activeEntries', 'todaySummary', 'todayBookings', 'futureBookings'));
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
            'email' => 'nullable|email|max:255|unique:flip_city_users',
            'phone' => 'nullable|string|max:50',
            'billing_zip' => 'required|string|max:10',
            'billing_city' => 'required|string|max:255',
            'billing_address' => 'required|string|max:255',
        ]);

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
            $mailData = [
                'subject'     => 'Regisztráció - Jelszó beállítása',
                'success_res' => 'Sikeresen regisztráltunk a rendszerbe!',
                'desc'        => 'Kérjük, állítsa be jelszavát az alábbi linken: <br><a href="' . url('/password/reset/' . Str::random(60)) . '">Jelszó beállítása</a>',
            ];
            Mail::to($user->email)->send(new FlipCityMail($user, $mailData));
        }

        return redirect()->route('flip-city.admin.users.index')
            ->with('success', 'Ügyfél sikeresen hozzáadva.');
    }
}
