@extends('site.layouts.layout')

@section('title', 'Profil - ' . config('app.name'))
@section('og_title', 'Profil - ' . config('app.name'))

@section('extendCSS')
    @if($flipCitySettings['css_published'])
        <link rel="stylesheet" href="{{ asset('packages/flip-city/css/site/flip-city.css') }}">
    @endif
@endsection

@section('content')
<div class="flipcity flipcity-profile">
    <div class="container py-5">
        <div class="row">
            <div class="col-md-4 mx-auto">
                <div class="card shadow mb-4">
                    <div class="card-body text-center">
                        <h5 class="card-title">Saját QR kód</h5>
                        <div class="qr-code-container mb-3 p-3 bg-light d-inline-block border qr-code-svg">
                            <style>.qr-code-svg svg { width: 100%; height: auto; max-width: 250px; }</style>
                            {!! $qrCode !!}
                        </div>
                        <p class="text-muted small">Ezzel a kóddal tudsz belépni a trambulin parkba.</p>
                        <a href="{{ route('flip-city.profile.print-qr') }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print mr-1"></i> Nyomtatás</a>
                        <hr>
                        <button class="btn btn-primary btn-block" data-toggle="modal" data-target="#profileDataModal">
                            <i class="fas fa-user mr-1"></i> Profil adatok
                        </button>
                        <button class="btn btn-info btn-block" data-toggle="modal" data-target="#allBookingsModal">
                            <i class="fas fa-list mr-1"></i> Összes foglalás
                        </button>
                        @if($flipCitySettings['billing_enabled'])
                        <button class="btn btn-success btn-block" data-toggle="modal" data-target="#invoicesModal">
                            <i class="fas fa-file-invoice mr-1"></i> Számláim
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-8 @if($flipCitySettings['show_profile_booking'] || $activeEntries->isNotEmpty() || $upcomingBookings->isNotEmpty()) @else d-none @endif">
                @if($activeEntries->isNotEmpty())
                <div class="card shadow mt-4 border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0 font-weight-bold"><i class="fas fa-clock mr-2"></i>Aktuális Belépések</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kezdés</th>
                                    <th>Létszám</th>
                                    <th>Eltelt idő</th>
                                    <th class="text-right">Várható díj</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeEntries as $entry)
                                <tr data-id="{{ $entry->id }}" data-rate="{{ $entry->rate }}">
                                    <td>{{ $entry->start_time->format('H:i') }}</td>
                                    <td class="guest-count">{{ $entry->guest_count }} fő</td>
                                    <td class="elapsed-time" data-start="{{ $entry->start_time->toISOString() }}">
                                        @php
                                            $diffInSeconds = $entry->start_time->diffInSeconds(now());
                                            $durationMinutes = ceil($diffInSeconds / 60);
                                            if ($durationMinutes < 1) $durationMinutes = 1;
                                        @endphp
                                        {{ $durationMinutes }} perc
                                    </td>
                                    <td class="text-right text-warning font-weight-bold expected-fee">
                                        {{ number_format(round(($durationMinutes / 60) * $entry->rate * $entry->guest_count), 0, ',', ' ') }} Ft
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($upcomingBookings->isNotEmpty())
                <div class="card shadow mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-check mr-2"></i>Közelgő Foglalásaid</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Dátum</th>
                                    <th>Időpont</th>
                                    <th>Létszám</th>
                                    <th>Státusz</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($upcomingBookings as $booking)
                                <tr>
                                    <td>{{ $booking->booking_date }}</td>
                                    <td>{{ date('H:i', strtotime($booking->booking_time)) }}</td>
                                    <td>{{ $booking->guest_count }} fő</td>
                                    <td>
                                        <span class="badge badge-{{ $booking->status === 'confirmed' ? 'success' : 'secondary' }}">
                                            {{ $booking->status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                @if($flipCitySettings['show_profile_booking'])
                    <div class="card shadow mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Foglalás</h5>
                        </div>
                        <div class="card-body">
                        <form action="{{ route('flip-city.booking.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Dátum</label>
                                        <input type="date" name="booking_date" class="form-control" required min="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Időpont</label>
                                        <input type="time" name="booking_time" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Létszám</label>
                                        <input type="number" name="guest_count" class="form-control" required min="1">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>Megjegyzés (opcionális)</label>
                                        <textarea name="comments" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info">Foglalás rögzítése</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Profil adatok Modal -->
<div class="modal fade" id="profileDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Profil adatok</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">Név:</div>
                    <div class="col-sm-8">{{ $user->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">E-mail:</div>
                    <div class="col-sm-8">{{ $user->email }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">Telefonszám:</div>
                    <div class="col-sm-8">{{ $user->phone ?? '-' }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">Számlázási név:</div>
                    <div class="col-sm-8">{{ $user->name }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">Számlázási cím:</div>
                    <div class="col-sm-8">{{ $user->billing_zip }} {{ $user->billing_city }}, {{ $user->billing_address }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 font-weight-bold">Státusz:</div>
                    <div class="col-sm-8">
                        @if($user->is_blocked)
                            <span class="badge badge-danger">Tiltva</span>
                        @elseif($user->is_active)
                            <span class="badge badge-success">Aktív</span>
                        @else
                            <span class="badge badge-warning">Inaktív</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Bezárás</button>
            </div>
        </div>
    </div>
</div>

<!-- Összes foglalás Modal -->
<div class="modal fade" id="allBookingsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Összes foglalás</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Dátum</th>
                                <th>Időpont</th>
                                <th>Létszám</th>
                                <th>Státusz</th>
                                <th>Megjegyzés</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($allBookings as $booking)
                            <tr>
                                <td>{{ $booking->booking_date }}</td>
                                <td>{{ date('H:i', strtotime($booking->booking_time)) }}</td>
                                <td>{{ $booking->guest_count }} fő</td>
                                <td>
                                    <span class="badge badge-{{ $booking->status === 'confirmed' ? 'success' : 'secondary' }}">
                                        {{ $booking->status }}
                                    </span>
                                </td>
                                <td><small>{{ $booking->comments ?? '-' }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">Még nincsenek foglalásaid.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Bezárás</button>
            </div>
        </div>
    </div>
</div>

<!-- Számlák Modal -->
<div class="modal fade" id="invoicesModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Számláim</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Dátum</th>
                                <th>Számlaszám</th>
                                <th class="text-right">Összeg</th>
                                <th class="text-center">Letöltés</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->created_at->format('Y.m.d H:i') }}</td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td class="text-right">{{ number_format($invoice->amount, 0, ',', ' ') }} Ft</td>
                                <td class="text-center">
                                    @if($invoice->invoice_url)
                                        <a href="{{ $invoice->invoice_url }}" target="_blank" class="btn btn-sm btn-outline-success">
                                            <i class="fas fa-file-pdf mr-1"></i> Megnyitás
                                        </a>
                                    @else
                                        <span class="text-muted small">N/A</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4">Még nincsenek számláid.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Bezárás</button>
            </div>
        </div>
    </div>
</div>

@section('script')
    @if($flipCitySettings['js_published'])
        <script src="{{ asset('packages/flip-city/js/site/flip-city.js') }}"></script>
    @endif
@endsection

@endsection
