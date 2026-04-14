@extends("admin.layouts.layout")

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flip-City Dashboard</h1>
        <div>
            <a href="{{ route('flip-city.admin.users.index') }}" class="btn btn-sm btn-outline-primary shadow-sm mr-1">
                <i class="fas fa-users fa-sm mr-1"></i> Felhasználók
            </a>
            <a href="{{ route('flip-city.admin.invoices') }}" class="btn btn-sm btn-outline-secondary shadow-sm mr-1">
                <i class="fas fa-file-invoice fa-sm mr-1"></i> Számlák
            </a>
            <button class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus fa-sm text-white-50 mr-1"></i> Új Ügyfél
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Aktív Vendégek ({{ $activeEntries->count() }})</h6>
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#scanQrModal">
                        <i class="fas fa-qrcode mr-1"></i> QR Beolvasás
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Név</th>
                                    <th>Belépés</th>
                                    <th>Eltelt idő</th>
                                    <th class="text-center">Fő</th>
                                    <th class="text-center">Várható díj</th>
                                    <th class="text-center">Művelet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeEntries as $entry)
                                <tr>
                                    <td class="font-weight-bold">{{ $entry->user->name ?? '–' }}</td>
                                    <td>{{ $entry->start_time->format('H:i') }}</td>
                                    <td class="elapsed-time" data-start="{{ $entry->start_time->toISOString() }}">
                                        {{ $entry->start_time->diffInMinutes() }} perc
                                    </td>
                                    <td class="text-center">{{ $entry->guest_count }}</td>
                                    <td class="text-center text-warning font-weight-bold">
                                        {{ number_format(round(($entry->start_time->diffInMinutes() / 60) * $entry->rate * $entry->guest_count), 0, ',', ' ') }} Ft
                                    </td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-danger checkout-btn"
                                                data-id="{{ $entry->id }}"
                                                title="Kiléptetés és fizetés">
                                            <i class="fas fa-sign-out-alt mr-1"></i> Kilép
                                        </button>
                                        @if($entry->guest_count > 1)
                                        <button class="btn btn-sm btn-info partial-btn"
                                                data-id="{{ $entry->id }}"
                                                data-max="{{ $entry->guest_count }}"
                                                title="Részleges távozás">
                                            <i class="fas fa-users mr-1"></i> Részleges
                                        </button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-door-open fa-2x mb-2 d-block"></i>
                                        Jelenleg nincs aktív vendég a rendszerben.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Napi Bevétel ({{ date('Y.m.d') }})</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 text-success"><i class="fas fa-money-bill-wave fa-2x"></i></div>
                        <div>
                            <div class="small text-muted">Készpénz</div>
                            <div class="h5 mb-0 font-weight-bold text-success">
                                {{ number_format($todaySummary->total_cash ?? 0, 0, ',', ' ') }} Ft
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 text-primary"><i class="fas fa-credit-card fa-2x"></i></div>
                        <div>
                            <div class="small text-muted">Bankkártya</div>
                            <div class="h5 mb-0 font-weight-bold text-primary">
                                {{ number_format($todaySummary->total_card ?? 0, 0, ',', ' ') }} Ft
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-4">
                        <div class="mr-3 text-warning"><i class="fas fa-robot fa-2x"></i></div>
                        <div>
                            <div class="small text-muted">Automatikus</div>
                            <div class="h5 mb-0 font-weight-bold text-warning">
                                {{ number_format($todaySummary->total_auto ?? 0, 0, ',', ' ') }} Ft
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div class="h5 font-weight-bold text-dark mb-3">
                        Összesen:
                        {{ number_format(($todaySummary->total_cash ?? 0) + ($todaySummary->total_card ?? 0) + ($todaySummary->total_auto ?? 0), 0, ',', ' ') }} Ft
                    </div>
                    @if($todaySummary && $todaySummary->is_closed)
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-check-circle mr-1"></i>
                            A nap lezárva: {{ $todaySummary->closed_at?->format('H:i') }}
                        </div>
                    @else
                        <form action="{{ route('flip-city.admin.close-day') }}" method="POST"
                              onsubmit="return confirm('Biztosan lezárja a mai napot?')">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-lock mr-1"></i> Nap Lezárása
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Új Ügyfél Hozzáadása</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form action="{{ route('flip-city.admin.add-user') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Név <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="Teljes név">
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-muted small">(opcionális)</span></label>
                        <input type="email" name="email" class="form-control" placeholder="pelda@email.com">
                        <small class="form-text text-muted">Ha megadja, jelszó-beállító emailt küldünk.</small>
                    </div>
                    <div class="form-group">
                        <label>Telefonszám</label>
                        <input type="text" name="phone" class="form-control" placeholder="+36 30 ...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Mentés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- QR Beolvasó Modal -->
<div class="modal fade" id="scanQrModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-qrcode mr-2"></i>QR Kód Beolvasás</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="qr-reader" style="width: 100%; margin-bottom: 15px;"></div>
                <div class="form-group">
                    <label for="qr_token_input">QR Token (manuális bevitel vagy olvasó)</label>
                    <div class="input-group">
                        <input type="text" id="qr_token_input" class="form-control form-control-lg"
                               placeholder="Olvassa be a kódot..." autocomplete="off">
                        <div class="input-group-append">
                            <button type="button" id="manual_scan_btn" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Nyomja meg Entert vagy kattintson a gombra a feldolgozáshoz.</small>
                </div>
                <div id="qr_guest_count_wrap" class="form-group d-none">
                    <label for="qr_guest_count">Vendégek száma</label>
                    <input type="number" id="qr_guest_count" class="form-control" value="1" min="1" max="50">
                </div>
                <div id="qr_result" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Bezár</button>
            </div>
        </div>
    </div>
</div>

<!-- Kiléptetés / Fizetés Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-cash-register mr-2"></i>Kiléptetés és Fizetés</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="checkout_form">
                @csrf
                <input type="hidden" name="entry_id" id="checkout_entry_id">
                <input type="hidden" name="payment_method" id="payment_method_hidden" value="cash">
                <div class="modal-body">
                    <div class="alert alert-info mb-4">
                        <div class="h4 mb-1">Fizetendő összeg:</div>
                        <div class="display-4 font-weight-bold text-danger">
                            <span id="checkout_amount">0</span> Ft
                        </div>
                        <small class="text-muted" id="checkout_duration"></small>
                    </div>

                    <div class="form-group">
                        <label>Fizetési mód</label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <label class="btn btn-outline-success active">
                                <input type="radio" name="payment_method" id="pay_cash" value="cash" checked>
                                <i class="fas fa-money-bill-wave mr-1"></i> Készpénz
                            </label>
                            <label class="btn btn-outline-primary">
                                <input type="radio" name="payment_method" id="pay_card" value="card">
                                <i class="fas fa-credit-card mr-1"></i> Bankkártya
                            </label>
                        </div>
                    </div>

                    <div id="cash_details">
                        <div class="form-group">
                            <label for="cash_received">Kapott összeg (Ft)</label>
                            <input type="number" name="cash_received" id="cash_received"
                                   class="form-control form-control-lg" min="0" step="100" placeholder="0">
                        </div>
                        <div class="alert alert-success">
                            Visszajáró: <strong><span id="change_amount">0</span> Ft</strong>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check mr-1"></i> Fizetés Lezárása
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Részleges Kiléptetés Modal -->
<div class="modal fade" id="partialCheckoutModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-users mr-2"></i>Részleges Kiléptetés</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Összesen <strong id="partial_total_guests">0</strong> fő tartózkodik bent.</p>
                <div class="form-group">
                    <label for="partial_leaving_count">Hányan távoznak?</label>
                    <input type="number" id="partial_leaving_count" class="form-control form-control-lg"
                           value="1" min="1" max="1">
                </div>
                <div id="partial_result"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                <button type="button" id="partial_confirm_btn" class="btn btn-warning">
                    <i class="fas fa-check mr-1"></i> Részleges Kiléptetés
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="/plugins/qrcode-scanner/html5-qrcode.min.js"></script>
    <script src="{{ asset('packages/flip-city/js/admin/flip-city.js') }}"></script>
@endpush
{{--<script src="/js/qr-code-scanner.js"></script>--}}
<link rel="stylesheet" href="{{ asset('packages/flip-city/css/admin/flip-city.css') }}">
@endsection
