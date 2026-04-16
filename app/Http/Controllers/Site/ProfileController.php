<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use Weboldalnet\FlipCity\Models\Booking;
use Weboldalnet\FlipCity\Services\QRCodeService;

class ProfileController extends SiteExtendedController
{
    public function index()
    {
        $user = auth()->user();

        // QR kód lekérése a modellből, vagy generálása ha hiányzik
        $qrCode = null;
        if ($user && $user->qr_code_token) {
            if (!$user->qr_code_svg) {
                $user->qr_code_svg = QRCodeService::generateQRCode($user->qr_code_token);
                $user->save();
            }
            $qrCode = $user->qr_code_svg;
        }

        // Aktuális belépések és foglalások
        $activeEntries = $user ? $user->entries()->whereNull('end_time')->get() : collect();
        $upcomingBookings = $user ? $user->bookings()->where('booking_date', '>=', now()->toDateString())->orderBy('booking_date')->orderBy('booking_time')->get() : collect();
        $allBookings = $user ? $user->bookings()->orderByDesc('booking_date')->orderByDesc('booking_time')->get() : collect();

        return view('flip-city::site.flip-city.profile', compact('user', 'qrCode', 'activeEntries', 'upcomingBookings', 'allBookings'));
    }
}
