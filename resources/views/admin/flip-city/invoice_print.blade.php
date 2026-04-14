<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Számla - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 14px; }
        .container { width: 100%; max-width: 800px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .header { text-align: center; margin-bottom: 30px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: bold; }
        .footer { margin-top: 50px; text-align: center; font-style: italic; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Flip-City Trambulin Park</h1>
            <p>Nyugta / Számla</p>
        </div>

        <div class="row">
            <div>
                <p><span class="label">Sorszám:</span> {{ $invoice->invoice_number }}</p>
                <p><span class="label">Dátum:</span> {{ $invoice->created_at->format('Y-m-d H:i') }}</p>
            </div>
            <div style="text-align: right;">
                <p><span class="label">Vevő:</span> {{ $invoice->user->name }}</p>
                <p><span class="label">E-mail:</span> {{ $invoice->user->email ?? '-' }}</p>
            </div>
        </div>

        <hr>

        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="background-color: #f2f2f2;">
                    <th style="padding: 10px; text-align: left;">Megnevezés</th>
                    <th style="padding: 10px; text-align: right;">Összeg</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 10px;">Trambulin belépő ({{ $invoice->entry->guest_count }} fő)</td>
                    <td style="padding: 10px; text-align: right;">{{ number_format($invoice->amount, 0, ',', ' ') }} Ft</td>
                </tr>
            </tbody>
            <tfoot>
                <tr style="border-top: 2px solid #000;">
                    <td style="padding: 10px; font-weight: bold;">ÖSSZESEN:</td>
                    <td style="padding: 10px; text-align: right; font-weight: bold;">{{ number_format($invoice->amount, 0, ',', ' ') }} Ft</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top: 20px;">
            <p><span class="label">Fizetési mód:</span> {{ $invoice->payment_method === 'cash' ? 'Készpénz' : 'Bankkártya' }}</p>
            @if($invoice->payment_method === 'cash')
                <p><span class="label">Kapott összeg:</span> {{ number_format($invoice->cash_received, 0, ',', ' ') }} Ft</p>
                <p><span class="label">Visszajáró:</span> {{ number_format($invoice->change_given, 0, ',', ' ') }} Ft</p>
            @endif
        </div>

        <div class="footer">
            <p>Köszönjük a látogatást!</p>
        </div>

        <div class="no-print" style="margin-top: 20px; text-align: center;">
            <button onclick="window.print()">Nyomtatás</button>
            <button onclick="window.close()">Bezárás</button>
        </div>
    </div>
</body>
</html>
