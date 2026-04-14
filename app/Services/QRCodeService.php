<?php

namespace Weboldalnet\FlipCity\Services;

use Illuminate\Support\HtmlString;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * TODO: https://www.itsolutionstuff.com/post/how-to-generate-qr-code-in-laravelexample.html
 *
 * A használathoz szükséges az alábbi composer csomag telepítése
 * TODO: composer require simplesoftwareio/simple-qrcode
 */
class QRCodeService
{
    public static function generateQRCode($qrcodeId)
    {
        /** @var HtmlString $qrcode */
        $qrcode = QrCode::format('svg')
            ->size(300)
            ->generate($qrcodeId);

        return $qrcode->toHtml();
    }

}
