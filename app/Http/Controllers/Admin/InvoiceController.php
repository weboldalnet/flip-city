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
        ]);

        $entry = Entry::findOrFail($validated['entry_id']);

        // Lezárjuk a belépést, ha még nincs lezárva
        if (!$entry->end_time) {
            $entry->end_time = now();
            $durationMinutes = max(1, $entry->start_time->diffInMinutes($entry->end_time));
            $entry->total_cost = round(($durationMinutes / 60) * $entry->rate * $entry->guest_count);
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

        $invoice = Invoice::create([
            'entry_id'       => $entry->id,
            'user_id'        => $entry->user_id,
            'amount'         => $amount,
            'payment_method' => $validated['payment_method'],
            'cash_received'  => $validated['cash_received'] ?? null,
            'change_given'   => $change,
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
        ]);

        // Napi összesítő frissítése
        $summary = DailySummary::firstOrCreate(['summary_date' => date('Y-m-d')]);
        if ($invoice->payment_method === 'cash') {
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
}
