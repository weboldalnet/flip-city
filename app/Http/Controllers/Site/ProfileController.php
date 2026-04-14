<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ProfileController extends SiteExtendedController
{
    public function index()
    {
        $user = auth()->user();
        $qrCode = QrCode::size(200)->generate($user->qr_code_token);

        return view('flip-city::site.flip-city.profile', compact('user', 'qrCode'));
    }
}
