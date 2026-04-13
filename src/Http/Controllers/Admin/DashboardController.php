<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Weboldalnet\FlipCity\Models\DailySummary;
use Weboldalnet\FlipCity\Models\Invoice;
use Weboldalnet\FlipCity\Models\User;
use Weboldalnet\FlipCity\Models\Entry;
use Carbon\Carbon;

class DashboardController extends FlipCityAdminController
{
    public function index()
    {
        $activeEntries = Entry::with('user')->whereNull('end_time')->get();
        $todaySummary = DailySummary::where('summary_date', date('Y-m-d'))->first();

        return view('flip-city::admin.flip-city.dashboard', compact('activeEntries', 'todaySummary'));
    }

    public function closeDay()
    {
        $today = date('Y-m-d');
        $summary = DailySummary::firstOrCreate(['summary_date' => $today]);

        $invoices = Invoice::whereDate('created_at', $today)->get();

        $summary->total_cash = $invoices->where('payment_method', 'cash')->sum('amount');
        $summary->total_card = $invoices->where('payment_method', 'card')->sum('amount');
        $summary->total_auto = $invoices->where('payment_method', 'auto')->sum('amount');
        $summary->is_closed = true;
        $summary->closed_at = now();
        $summary->save();

        return redirect()->back()->with('success', 'A nap sikeresen lezárva.');
    }

    public function addUser(Request $request)
    {
        // Manuális ügyfél hozzáadás admin által
    }
}
