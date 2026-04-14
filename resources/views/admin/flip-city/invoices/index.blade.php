@extends("admin.layouts.layout")

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flip-City – Számlák és nyugták</h1>
        <a href="{{ route('flip-city.admin.dashboard') }}" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm mr-1"></i> Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Számlák listája</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Számlaszám</th>
                            <th>Vendég</th>
                            <th>Összeg</th>
                            <th>Fizetési mód</th>
                            <th>Visszajáró</th>
                            <th>Dátum</th>
                            <th class="text-center">Művelet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                        <tr>
                            <td class="font-weight-bold small">{{ $invoice->invoice_number }}</td>
                            <td>
                                @if($invoice->user)
                                    <a href="{{ route('flip-city.admin.users.show', $invoice->user_id) }}"
                                       class="text-dark">
                                        {{ $invoice->user->name }}
                                    </a>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td class="font-weight-bold text-right">
                                {{ number_format($invoice->amount, 0, ',', ' ') }} Ft
                            </td>
                            <td>
                                @if($invoice->payment_method === 'cash')
                                    <span class="badge badge-success"><i class="fas fa-money-bill-wave mr-1"></i>Készpénz</span>
                                @elseif($invoice->payment_method === 'card')
                                    <span class="badge badge-primary"><i class="fas fa-credit-card mr-1"></i>Kártya</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-robot mr-1"></i>Automatikus</span>
                                @endif
                            </td>
                            <td class="text-right">
                                @if($invoice->change_given !== null && $invoice->change_given > 0)
                                    {{ number_format($invoice->change_given, 0, ',', ' ') }} Ft
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $invoice->created_at->format('Y.m.d H:i') }}</td>
                            <td class="text-center">
                                <a href="{{ route('flip-city.admin.invoices.print', $invoice->id) }}"
                                   target="_blank"
                                   class="btn btn-xs btn-outline-secondary" title="Nyomtatás">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-file-invoice fa-2x mb-2 d-block"></i>
                                Még nem készült számla.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Összesen: {{ $invoices->total() }} számla</small>
                {{ $invoices->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<link rel="stylesheet" href="{{ asset('packages/flip-city/css/admin/flip-city.css') }}">
@endsection
