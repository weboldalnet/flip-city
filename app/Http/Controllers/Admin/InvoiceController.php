<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Weboldalnet\FlipCity\Models\DailySummary;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\Invoice;

class InvoiceController extends FlipCityAdminController
{
    public function index()
    {
        $invoices = Invoice::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(30);

        return view('flip-city::admin.flip-city.invoices.index', compact('invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_id'       => 'required|exists:flip_city_entries,id',
            'payment_method' => 'required|in:cash,card',
            'cash_received'  => 'required_if:payment_method,cash|nullable|numeric|min:0',
            'final_cost'     => 'nullable|numeric|min:0',
        ]);

        $entry = Entry::findOrFail($validated['entry_id']);

        // Lezárjuk a belépést, ha még nincs lezárva
        if (!$entry->end_time) {
            $entry->end_time = now();

            // Ha a frontend küldött egy rögzített (megállított) összeget, használjuk azt
            if (isset($validated['final_cost'])) {
                $entry->total_cost = (float) $validated['final_cost'];
            } else {
                $diffInSeconds = $entry->start_time->diffInSeconds($entry->end_time);
                $durationMinutes = ceil($diffInSeconds / 60);
                if ($durationMinutes < 1) $durationMinutes = 1;
                $entry->total_cost = round(($durationMinutes / 60) * $entry->rate * $entry->guest_count);
            }
            $entry->save();
        }

        $amount = $entry->total_cost;
        $change = 0;

        if ($validated['payment_method'] === 'cash') {
            $cashReceived = (float) ($validated['cash_received'] ?? 0);
            if ($cashReceived < $amount) {
                return response()->json(['success' => false, 'message' => 'A kapott összeg kevesebb a fizetendőnél!']);
            }
            $change = $cashReceived - $amount;
        }

        // Számlázás integrálása
        $invoice = null;
        if (config('flip-city.billing_enabled', true)) {
            try {
                $invoiceResponse = \Weboldalnet\FlipCity\Services\InvoiceService::createInvoiceForEntry($entry, $amount);
                if ($invoiceResponse && $invoiceResponse->isSuccess()) {
                    $invoice = Invoice::create([
                        'entry_id'       => $entry->id,
                        'user_id'        => $entry->user_id,
                        'amount'         => $amount,
                        'payment_method' => $validated['payment_method'],
                        'cash_received'  => $validated['cash_received'] ?? null,
                        'change_given'   => $change,
                        'invoice_number' => $invoiceResponse->getDocumentNumber(),
                        'invoice_url'    => '',
                    ]);

                    $invoice->invoice_url = route('flip-city.admin.invoices.download', $invoice->id);
                    $invoice->save();
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Számlázási hiba az InvoiceController-ben: ' . $e->getMessage());
            }
        }

        // Ha nincs számlázás, akkor is rögzítsük a fizetés tényét az adatbázisban (opcionális, de a bevételhez kellhet egy rekord)
        // Ha a projektben az Invoice rekord a bevétel alapja, akkor hozzuk létre bizonylatszám nélkül is.
        if (!$invoice) {
            $invoice = Invoice::create([
                'entry_id'       => $entry->id,
                'user_id'        => $entry->user_id,
                'amount'         => $amount,
                'payment_method' => $validated['payment_method'],
                'cash_received'  => $validated['cash_received'] ?? null,
                'change_given'   => $change,
                'invoice_number' => null,
                'invoice_url'    => null,
            ]);
        }

        // Napi összesítő frissítése
        $summary = DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')]);
        if ($validated['payment_method'] === 'cash') {
            $summary->total_cash += $amount;
        } else {
            $summary->total_card += $amount;
        }
        $summary->save();

        return response()->json([
            'success'     => true,
            'message'     => 'Fizetés sikeresen rögzítve!',
            'change'      => $change,
            'invoice_id'  => $invoice->id,
        ]);
    }

    public function print(Invoice $invoice)
    {
        $invoice->load('user', 'entry');
        return view('flip-city::admin.flip-city.invoices.print', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        // Jogosultság ellenőrzés: Admin vagy a számla tulajdonosa
        if (!auth('admin')->check() && auth()->id() !== $invoice->user_id) {
            abort(403);
        }

        if (!$invoice->invoice_number) {
            return redirect()->back()->with('error', 'A számla nem található (nincs bizonylatszám).');
        }

        try {
            $response = \Weboldalnet\FlipCity\Services\InvoiceService::getInvoicePdf($invoice->invoice_number);

            if ($response && $response->isSuccess()) {
                $pdfData = $response->getPdfData();

                if (!$pdfData) {
                    return redirect()->back()->with('error', 'A számla PDF tartalma üres.');
                }

                return response($pdfData)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $invoice->invoice_number . '.pdf"');
            } else {
                $error = $response ? $response->getErrorMessage() : 'Ismeretlen hiba a Számla Agent-től.';
                return redirect()->back()->with('error', 'Hiba a számla letöltésekor: ' . $error);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Számla letöltési hiba: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Hiba történt a számla lekérése közben.');
        }
    }
}
