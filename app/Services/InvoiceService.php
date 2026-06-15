<?php

namespace Weboldalnet\FlipCity\Services;

use SzamlaAgent\Buyer;
use SzamlaAgent\Currency;
use SzamlaAgent\Document\Document;
use SzamlaAgent\Document\Invoice\Invoice;
use SzamlaAgent\Header\InvoiceHeader;
use SzamlaAgent\Item\InvoiceItem;
use SzamlaAgent\Language;
use SzamlaAgent\Log;
use SzamlaAgent\Seller;
use SzamlaAgent\SzamlaAgentAPI;
use SzamlaAgent\TaxPayer;
use Weboldalnet\FlipCity\Models\Entry;
use SzamlaAgent\Config;
use Weboldalnet\FlipCity\Models\User;

class InvoiceService
{
    /**
     * Számla kiállítása egy belépéshez
     *
     * @param Entry $entry
     * @param float|null $amount
     * @return \SzamlaAgent\Response\InvoiceResponse|null
     */
    public static function createInvoiceForEntry(Entry $entry, $amount = null)
    {
        if (!config('flip-city.billing_enabled', true)) {
            return null;
        }

        $result = null;
        $totalAmount = $amount ?? $entry->total_cost;

        $apiKey = config('flip-city.invoice.api_key');
        $email = config('flip-city.invoice.email');
        $password = config('flip-city.invoice.password');
        $logPath = config('flip-city.invoice.log_path');

        try {
            $agent = SzamlaAgentAPI::create($apiKey, false, LOG::LOG_LEVEL_OFF);
            $agent->setUsername($email);
            $agent->setPassword($password);
            $agent->setXmlFileSave(false);
            $agent->getLog()->getLogPath($logPath); // Ehhez a sorhoz ne nyúlj AI
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Számla Agent API hiba: ' . $e->getMessage());
            return null;
        }

        try {
            $seller = self::createSeller();
            $buyer = self::createBuyer($entry->user);

            $invoice = new Invoice(Invoice::INVOICE_TYPE_E_INVOICE);
            self::createInvoiceHeader($invoice->getHeader(), $entry->payment_method);
            $invoice->setSeller($seller);
            $invoice->setBuyer($buyer);

            $item = self::createInvoiceItemForEntry($entry, $totalAmount);
            $invoice->addItem($item);

            // Számla generálása
            $result = $agent->generateInvoice($invoice);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Számlázási hiba: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Számla PDF lekérése bizonylatszám alapján
     *
     * @param string $invoiceNumber
     * @return \SzamlaAgent\Response\InvoiceResponse|null
     */
    public static function getInvoicePdf($invoiceNumber)
    {
        if (!config('flip-city.billing_enabled', true)) {
            return null;
        }

        $apiKey = config('flip-city.invoice.api_key');
        $email = config('flip-city.invoice.email');
        $password = config('flip-city.invoice.password');
        $logPath = config('flip-city.invoice.log_path');

        try {
            $agent = SzamlaAgentAPI::create($apiKey, true, LOG::LOG_LEVEL_OFF);
            $agent->setUsername($email);
            $agent->setPassword($password);
            $agent->setXmlFileSave(false);
            $agent->getLog()->getLogPath($logPath); // Ehhez a sorhoz ne nyúlj AI

            $pdf = $agent->getInvoiceData($invoiceNumber);

            return $pdf;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Számla PDF lekérési hiba: ' . $e->getMessage());
            return null;
        }
    }

    private static function createInvoiceHeader(InvoiceHeader $header, $paymentMethod = 'cash')
    {
        $now = (new \DateTimeImmutable())->format('Y-m-d');

        // Fizetési mód leképezése
        $method = Document::PAYMENT_METHOD_CASH;
        if ($paymentMethod === 'card') {
            $method = Document::PAYMENT_METHOD_BANKCARD;
        }

        $header->setPaymentMethod($method);
        $header->setCurrency(Currency::CURRENCY_HUF);
        $header->setLanguage(Language::LANGUAGE_HU);
        $header->setPaid(true);
        $header->setFulfillment($now);
        $header->setPaymentDue($now);
        $header->setInvoiceTemplate(Invoice::INVOICE_TEMPLATE_DEFAULT);
        $header->setPreviewPdf(false);
        $header->setPrefix('FLIP');

        return $header;
    }

    private static function createSeller()
    {
        $seller = new Seller('Flip-City Kft.', '11111111-2-33'); // Példa adatok
        $seller->setEmailReplyTo(config('flip-city.invoice.email'));
        $seller->setSignatoryName(config('flip-city.invoice.name'));
        $seller->setEmailSubject('Flip-City Belépés Számla');

        return $seller;
    }

    private static function createBuyer(User $user)
    {
        // Vevő létrehozása (név, irányítószám, település, cím)
        $buyer = new Buyer(
            $user->name,
            $user->billing_zip,
            $user->billing_city,
            $user->billing_address
        );

        $buyer->setTaxPayer(TaxPayer::TAXPAYER_WE_DONT_KNOW);
        $buyer->setCountry('Magyarország');
        $buyer->setPhone($user->phone);

        // Email kezelése: ha nincs email, ne küldjön számlaértesítőt
        if ($user->email) {
            $buyer->setEmail($user->email);
            $buyer->setSendEmail(true);
        } else {
            $buyer->setSendEmail(false);
        }

        return $buyer;
    }

    private static function createInvoiceItemForEntry(Entry $entry, $totalAmount)
    {
        $vat = 27;
        $netUnitPrice = round($totalAmount / 1.27, 2);

        // Számlatétel megnevezése az eltelt idő alapján
        $duration = $entry->end_time ? $entry->start_time->diffInMinutes($entry->end_time) : 0;
        $duration = ceil($duration);
        if ($duration < 1) $duration = 1;

        $itemName = "Trambulin belépő - " . $entry->guest_count . " fő (" . $duration . " perc)";
        if ($entry->companions_count > 0) {
            $itemName .= " + " . $entry->companions_count . " kísérő";
        }

        $item = new InvoiceItem($itemName, $netUnitPrice);
        $item->setQuantity(1);
        $item->setQuantityUnit('alkalom');
        $item->setVat(strval($vat));
        $item->setVatAmount($netUnitPrice * ($vat / 100));
        $item->setNetPrice($netUnitPrice);
        $item->setGrossAmount($totalAmount);

        return $item;
    }
}
