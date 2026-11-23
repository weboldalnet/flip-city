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

        if ($entry->end_time && Invoice::where('entry_id', $entry->id)->where('created_at', '>=', $entry->end_time)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ennek a belépésnek a díja már ki van fizetve.']);
        }

        if (!$entry->end_time) {
            $entry->end_time = now();
            $entry->total_cost = $this->payableCost($entry, $validated['final_cost'] ?? null);
            $entry->save();
        }

        $amount = Entry::payableAmount((float) $entry->total_cost, $validated['payment_method']);
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

        DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')])
            ->increment($validated['payment_method'] === 'cash' ? 'total_cash' : 'total_card', $amount);

        return response()->json([
            'success'     => true,
            'message'     => 'Fizetés sikeresen rögzítve!',
            'change'      => $change,
            'invoice_id'  => $invoice->id,
        ]);
    }

    private function payableCost(Entry $entry, $shownCost): float
    {
        $current = $entry->costBreakdown($entry->end_time)['total'];

        if ($shownCost === null) {
            return $current;
        }

        $earliest = $entry->costBreakdown($entry->end_time->copy()->subMinutes(5))['total'];
        $shownCost = (float) $shownCost;

        return $shownCost >= $earliest && $shownCost <= $current ? $shownCost : $current;
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
