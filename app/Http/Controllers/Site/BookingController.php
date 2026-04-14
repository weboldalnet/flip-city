<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Weboldalnet\FlipCity\Models\Booking;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required',
            'guest_count' => 'required|integer|min:1',
        ]);

        $booking = Booking::create([
            'user_id' => auth()->id(),
            'booking_date' => $validated['booking_date'],
            'booking_time' => $validated['booking_time'],
            'guest_count' => $validated['guest_count'],
            'qr_code_token' => 'BOOK-' . Str::uuid()->toString(),
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Foglalás sikeresen rögzítve.');
    }
}
