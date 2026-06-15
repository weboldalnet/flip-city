<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use Weboldalnet\FlipCity\Models\Booking;
use Weboldalnet\FlipCity\Models\FlipCitySettings;
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
        $invoices = $user ? $user->invoices()->orderByDesc('created_at')->get() : collect();

        return view('flip-city::site.flip-city.profile', compact('user', 'qrCode', 'activeEntries', 'upcomingBookings', 'allBookings', 'invoices'));
    }

    public function printQR()
    {
        $user = auth()->user();
        if (!$user) abort(403);

        $qrCode = $user->qr_code_svg;
        if (!$qrCode && $user->qr_code_token) {
            $qrCode = QRCodeService::generateQRCode($user->qr_code_token);
            $user->qr_code_svg = $qrCode;
            $user->save();
        }

        $printText = FlipCitySettings::get('profile_qr_print_text', config('flip-city.profile_qr_print_text', 'Kérjük, mutassa be ezt a kódot a belépéshez!'));

        return view('flip-city::site.flip-city.print-qr', compact('user', 'qrCode', 'printText'));
    }
}
