@extends('admin.layouts.layout')

@section('content')
<div class="container-fluid flip-city-admin">
    <h1 class="h3 mb-4 text-gray-800">Flip-City Dashboard</h1>

    <div class="row">
        <!-- Aktív vendégek -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aktív Vendégek ({{ $activeEntries->count() }})</h6>
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#scanQrModal">
                        <i class="fas fa-qrcode mr-1"></i> QR Beolvasás
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Név</th>
                                    <th>Belépés Ideje</th>
                                    <th>Eltelt Idő</th>
                                    <th>Vendégek</th>
                                    <th>Művelet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeEntries as $entry)
                                <tr>
                                    <td>{{ $entry->user->name }}</td>
                                    <td>{{ $entry->start_time->format('H:i:s') }}</td>
                                    <td>{{ $entry->start_time->diffForHumans(null, true) }}</td>
                                    <td>{{ $entry->guest_count }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-danger checkout-btn" data-id="{{ $entry->id }}">
                                            Kiléptetés
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Napi statisztika -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Napi Bevétel ({{ date('Y-m-d') }})</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Készpénz</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($todaySummary->total_cash ?? 0, 0, ',', ' ') }} Ft</div>
                    </div>
                    <div class="mb-3">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Bankkártya</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($todaySummary->total_card ?? 0, 0, ',', ' ') }} Ft</div>
                    </div>
                    <hr>
                    <form action="{{ route('flip-city.admin.close-day') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-block" {{ ($todaySummary && $todaySummary->is_closed) ? 'disabled' : '' }}>
                            Nap Lezárása
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Scan Modal -->
<div class="modal fade" id="scanQrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QR-kód Beolvasása</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="qrScanForm">
                    <div class="form-group">
                        <label>QR Token</label>
                        <input type="text" class="form-control" name="qr_code_token" id="qrTokenInput" required autofocus>
                    </div>
                    <div class="form-group">
                        <label>Vendégek száma</label>
                        <input type="number" class="form-control" name="guest_count" value="1" min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                <button type="button" class="btn btn-primary" id="submitScan">Beléptetés</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('packages/flip-city/css/admin/flip-city.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('packages/flip-city/js/admin/flip-city.js') }}"></script>
@endpush
