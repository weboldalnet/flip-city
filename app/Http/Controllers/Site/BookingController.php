<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Weboldalnet\FlipCity\Models\Booking;
use Weboldalnet\FlipCity\Models\FlipCitySettings;

class BookingController extends SiteExtendedController
{
    public function store(Request $request)
    {
        if (!FlipCitySettings::get('show_profile_booking', config('flip-city.show_profile_booking', true))) {
            return back()->with('error', 'A foglalás jelenleg nem elérhető.');
        }

        $from = FlipCitySettings::get('booking_from', config('flip-city.booking_from', '08:00'));
        $until = FlipCitySettings::get('booking_until', config('flip-city.booking_until', '16:00'));

        $validated = $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'guest_count' => 'required|integer|min:1',
            'comments' => 'nullable|string|max:1000',
        ]);

        if ($validated['booking_time'] < $from || $validated['booking_time'] > $until) {
            return back()->withInput()->withErrors(['booking_time' => "Időpontot {$from} és {$until} között lehet foglalni."]);
        }

        if ($validated['booking_date'] === now()->toDateString() && $validated['booking_time'] <= now()->format('H:i')) {
            return back()->withInput()->withErrors(['booking_time' => 'Mai napra csak még el nem múlt időpontot lehet foglalni.']);
        }

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'booking_date' => $validated['booking_date'],
            'booking_time' => $validated['booking_time'],
            'guest_count' => $validated['guest_count'],
            'comments' => $validated['comments'] ?? null,
            'qr_code_token' => 'BOOK-' . Str::uuid()->toString(),
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Foglalás sikeresen rögzítve.');
    }
}
