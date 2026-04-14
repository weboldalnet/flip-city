@extends("admin.layouts.layout")

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user mr-2 text-primary"></i>{{ $user->name }}
        </h1>
        <div>
            <a href="{{ route('flip-city.admin.users.index') }}" class="btn btn-sm btn-secondary shadow-sm mr-1">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> Vissza
            </a>
            <a href="{{ route('flip-city.admin.users.edit', $user->id) }}" class="btn btn-sm btn-warning shadow-sm">
                <i class="fas fa-edit fa-sm mr-1"></i> Szerkesztés
            </a>
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
        <!-- Alapadatok -->
        <div class="col-xl-4 col-lg-5 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3 d-flex align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Alapadatok</h6>
                    <div>
                        @if($user->is_blocked)
                            <span class="badge badge-danger">Tiltott</span>
                        @elseif(!$user->is_active)
                            <span class="badge badge-warning">Inaktív</span>
                        @else
                            <span class="badge badge-success">Aktív</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5 text-muted small">Azonosító</dt>
                        <dd class="col-7">#{{ $user->id }}</dd>

                        <dt class="col-5 text-muted small">Név</dt>
                        <dd class="col-7 font-weight-bold">{{ $user->name }}</dd>

                        <dt class="col-5 text-muted small">Email</dt>
                        <dd class="col-7">
                            @if($user->email)
                                <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted small">Telefon</dt>
                        <dd class="col-7">{{ $user->phone ?? '–' }}</dd>

                        <dt class="col-5 text-muted small">Egyenleg</dt>
                        <dd class="col-7">
                            <span class="font-weight-bold {{ $user->balance > 0 ? 'text-success' : 'text-muted' }}">
                                {{ number_format($user->balance, 0, ',', ' ') }} Ft
                            </span>
                        </dd>

                        <dt class="col-5 text-muted small">Bankkártya</dt>
                        <dd class="col-7">
                            @if($user->card_registered)
                                <span class="text-info"><i class="fas fa-credit-card mr-1"></i>Rögzítve</span>
                            @else
                                <span class="text-muted">Nincs rögzítve</span>
                            @endif
                        </dd>

                        <dt class="col-5 text-muted small">Számlázási adat</dt>
                        <dd class="col-7 small">{{ $user->billing_details ?? '–' }}</dd>

                        <dt class="col-5 text-muted small">QR Token</dt>
                        <dd class="col-7">
                            <code class="small">{{ $user->qr_code_token ?? '–' }}</code>
                        </dd>

                        @if($user->qr_code_svg)
                            <dt class="col-12 text-muted small mt-2">QR Kód</dt>
                            <dd class="col-12 text-center mt-2 p-3 bg-light border">
                                <style>.qr-code-svg-admin svg { width: 100%; height: auto; max-width: 150px; }</style>
                                <div class="qr-code-svg-admin">
                                    {!! $user->qr_code_svg !!}
                                </div>
                            </dd>
                        @endif

                        <dt class="col-5 text-muted small">Regisztrált</dt>
                        <dd class="col-7 small">{{ $user->created_at->format('Y.m.d H:i') }}</dd>
                    </dl>
                </div>
                <div class="card-footer">
                    <div class="d-flex flex-wrap gap-1">
                        <form action="{{ route('flip-city.admin.users.toggle-active', $user->id) }}" method="POST" class="d-inline mr-1">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-secondary' : 'btn-success' }}">
                                <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }} mr-1"></i>
                                {{ $user->is_active ? 'Deaktiválás' : 'Aktiválás' }}
                            </button>
                        </form>
                        <form action="{{ route('flip-city.admin.users.toggle-blocked', $user->id) }}" method="POST" class="d-inline mr-1">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $user->is_blocked ? 'btn-outline-success' : 'btn-danger' }}">
                                <i class="fas {{ $user->is_blocked ? 'fa-lock-open' : 'fa-lock' }} mr-1"></i>
                                {{ $user->is_blocked ? 'Tiltás feloldása' : 'Letiltás' }}
                            </button>
                        </form>
                        <form action="{{ route('flip-city.admin.users.generate-qr', $user->id) }}" method="POST" class="d-inline mr-1">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary" title="QR Újragenerálása">
                                <i class="fas fa-qrcode mr-1"></i> QR Újra
                            </button>
                        </form>
                        @if($user->email)
                            <form action="{{ route('flip-city.admin.users.send-password-reset', $user->id) }}" method="POST" class="d-inline mr-1">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-envelope mr-1"></i> Jelszó-reset email
                                </button>
                            </form>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-danger"
                                data-toggle="modal" data-target="#deleteModal">
                            <i class="fas fa-trash mr-1"></i> Törlés
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <!-- Belépési előzmények -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-1"></i> Belépési Előzmények
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Belépés</th>
                                    <th>Kilépés</th>
                                    <th class="text-center">Fő</th>
                                    <th class="text-right">Díj</th>
                                    <th class="text-right">Összeg</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($entries as $entry)
                                <tr>
                                    <td class="small">{{ $entry->start_time->format('Y.m.d H:i') }}</td>
                                    <td class="small">
                                        @if($entry->end_time)
                                            {{ $entry->end_time->format('Y.m.d H:i') }}
                                            @if($entry->is_auto_closed)
                                                <span class="badge badge-warning badge-sm ml-1" title="Automatikusan zárva">auto</span>
                                            @endif
                                        @else
                                            <span class="badge badge-success">Aktív</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $entry->guest_count }}</td>
                                    <td class="text-right small">{{ number_format($entry->rate, 0, ',', ' ') }} Ft/h</td>
                                    <td class="text-right font-weight-bold">
                                        @if($entry->total_cost !== null)
                                            {{ number_format($entry->total_cost, 0, ',', ' ') }} Ft
                                        @else
                                            <span class="text-muted">–</span>
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Nincs belépési előzmény.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Foglalások -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-1"></i> Foglalások
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Dátum</th>
                                    <th>Időpont</th>
                                    <th class="text-center">Vendégek</th>
                                    <th>QR Token</th>
                                    <th>Státusz</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bookings as $booking)
                                <tr>
                                    <td class="small">{{ $booking->booking_date }}</td>
                                    <td class="small">{{ $booking->booking_time }}</td>
                                    <td class="text-center">{{ $booking->guest_count }}</td>
                                    <td><code class="small">{{ $booking->qr_code_token }}</code></td>
                                    <td>
                                        @php
                                            $statusLabels = ['pending' => 'Függőben', 'confirmed' => 'Visszaigazolt', 'cancelled' => 'Lemondva'];
                                            $statusClasses = ['pending' => 'warning', 'confirmed' => 'success', 'cancelled' => 'danger'];
                                        @endphp
                                        <span class="badge badge-{{ $statusClasses[$booking->status] ?? 'secondary' }}">
                                            {{ $statusLabels[$booking->status] ?? $booking->status }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Nincs foglalás.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle mr-2"></i>Törlés megerősítése</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p>Biztosan törli <strong>{{ $user->name }}</strong> felhasználót?</p>
                <p class="text-danger small">Ez a művelet nem visszavonható! Minden kapcsolódó adat (belépések, foglalások) is törlődik.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                <form action="{{ route('flip-city.admin.users.destroy', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash mr-1"></i> Törlés
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="{{ asset('packages/flip-city/css/admin/flip-city.css') }}">
@endsection
