<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Weboldalnet\FlipCity\Models\Invoice;
use Weboldalnet\FlipCity\Models\Entry;
use Weboldalnet\FlipCity\Models\DailySummary;
use Illuminate\Support\Str;

class InvoiceController extends FlipCityAdminController
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'entry_id' => 'required|exists:flip_city_entries,id',
            'payment_method' => 'required|in:cash,card',
            'cash_received' => 'required_if:payment_method,cash|numeric|min:0',
        ]);

        $entry = Entry::findOrFail($validated['entry_id']);
        $amount = $entry->total_cost;
        $change = 0;

        if ($validated['payment_method'] === 'cash') {
            $change = $validated['cash_received'] - $amount;
            if ($change < 0) {
                return response()->json(['success' => false, 'message' => 'Kevés a kapott összeg!']);
            }
        }

        $invoice = Invoice::create([
            'entry_id' => $entry->id,
            'user_id' => $entry->user_id,
            'amount' => $amount,
            'payment_method' => $validated['payment_method'],
            'cash_received' => $validated['cash_received'] ?? null,
            'change_given' => $change,
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
            'success' => true,
            'message' => 'Fizetés sikeres',
            'change' => $change,
            'invoice_url' => route('flip-city.admin.invoices.print', $invoice->id)
        ]);
    }

    public function print(Invoice $invoice)
    {
        return view('flip-city::admin.flip-city.invoice_print', compact('invoice'));
    }
}
