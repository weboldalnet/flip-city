<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Számla – {{ $invoice->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px; }
        .header h1 { font-size: 24px; color: #2c3e50; }
        .header p { color: #666; margin-top: 5px; }
        .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 25px; }
        .invoice-meta div { flex: 1; }
        .invoice-meta div:last-child { text-align: right; }
        .invoice-meta h3 { font-size: 13px; color: #666; text-transform: uppercase; margin-bottom: 8px; }
        .invoice-meta p { font-size: 14px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th, table td { border: 1px solid #ddd; padding: 10px 12px; text-align: left; }
        table thead { background-color: #2c3e50; color: #fff; }
        table tbody tr:nth-child(even) { background-color: #f8f9fa; }
        .total-row { font-weight: bold; font-size: 16px; background-color: #eaf4e8 !important; }
        .payment-info { background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; padding: 15px; margin-bottom: 20px; }
        .payment-info h3 { margin-bottom: 10px; font-size: 14px; color: #666; text-transform: uppercase; }
        .payment-badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-cash { background: #d4edda; color: #155724; }
        .badge-card { background: #cce5ff; color: #004085; }
        .footer { text-align: center; margin-top: 40px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
        .no-print { margin-top: 20px; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { padding: 10px; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Flip-City Trambulinpark</h1>
    <p>Fizetési nyugta / Számla</p>
</div>

<div class="invoice-meta">
    <div>
        <h3>Vevő adatai</h3>
        @if($invoice->user)
            <p><strong>{{ $invoice->user->name }}</strong></p>
            @if($invoice->user->email)<p>{{ $invoice->user->email }}</p>@endif
            @if($invoice->user->phone)<p>{{ $invoice->user->phone }}</p>@endif
            @if($invoice->user->billing_details)<p>{{ $invoice->user->billing_details }}</p>@endif
        @else
            <p>Névtelen vendég</p>
        @endif
    </div>
    <div>
        <h3>Számla adatai</h3>
        <p><strong>Számlaszám:</strong> {{ $invoice->invoice_number }}</p>
        <p><strong>Dátum:</strong> {{ $invoice->created_at->format('Y.m.d H:i') }}</p>
        <p><strong>Fizetési mód:</strong>
            @if($invoice->payment_method === 'cash')
                <span class="payment-badge badge-cash">Készpénz</span>
            @elseif($invoice->payment_method === 'card')
                <span class="payment-badge badge-card">Bankkártya</span>
            @else
                Egyéb
            @endif
        </p>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>Megnevezés</th>
            <th style="width:120px; text-align:right;">Összeg</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                Trambulin belépő
                @if($invoice->entry)
                    <br>
                    <small style="color:#666;">
                        {{ $invoice->entry->start_time->format('H:i') }} –
                        {{ $invoice->entry->end_time ? $invoice->entry->end_time->format('H:i') : '–' }}
                        ({{ $invoice->entry->start_time->diffInMinutes($invoice->entry->end_time ?? now()) }} perc,
                        {{ $invoice->entry->guest_count }} fő)
                    </small>
                @endif
            </td>
            <td style="text-align:right;">{{ number_format($invoice->amount, 0, ',', ' ') }} Ft</td>
        </tr>
        <tr class="total-row">
            <td>Összesen</td>
            <td style="text-align:right;">{{ number_format($invoice->amount, 0, ',', ' ') }} Ft</td>
        </tr>
        @if($invoice->payment_method === 'cash' && $invoice->cash_received)
        <tr>
            <td>Kapott összeg</td>
            <td style="text-align:right;">{{ number_format($invoice->cash_received, 0, ',', ' ') }} Ft</td>
        </tr>
        <tr>
            <td><strong>Visszajáró</strong></td>
            <td style="text-align:right;"><strong>{{ number_format($invoice->change_given ?? 0, 0, ',', ' ') }} Ft</strong></td>
        </tr>
        @endif
    </tbody>
</table>

<div class="footer">
    <p>Köszönjük a látogatást! – Flip-City Trambulinpark</p>
    <p style="margin-top:5px;">Ez a bizonylat elektronikusan generált, aláírás nélkül érvényes.</p>
</div>

<div class="no-print">
    <button onclick="window.print()" style="padding:8px 20px; background:#2c3e50; color:#fff; border:none; border-radius:4px; cursor:pointer; font-size:14px;">
        &#128424; Nyomtatás
    </button>
    <a href="{{ route('flip-city.admin.invoices') }}" style="margin-left:10px; text-decoration:none; color:#666;">
        ← Vissza a számlákhoz
    </a>
</div>

</body>
</html>
