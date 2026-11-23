@extends('admin.layouts.layout')

@section('title', 'Beállítások - Flip-City')

@section('content')
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flip-City Beállítások</h1>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Általános és Számlázási Beállítások</h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('flip-city.admin.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Alap óradíj (Ft/óra)</label>
                            <div class="col-sm-8">
                                <input type="number" name="default_rate" class="form-control" value="{{ old('default_rate', $flipCitySettings['default_rate']) }}" min="0" required>
                                <small class="text-muted">A beléptetéskor használt alapértelmezett óradíj.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Kísérő/fő ára (Ft)</label>
                            <div class="col-sm-8">
                                <input type="number" name="companion_price" class="form-control" value="{{ old('companion_price', $flipCitySettings['companion_price']) }}" min="0" required>
                                <small class="text-muted">Egyszeri díj kísérőnként belépéskor.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Gyerek óradíj (Ft/óra)</label>
                            <div class="col-sm-8">
                                <input type="number" name="child_rate" class="form-control" value="{{ old('child_rate', $flipCitySettings['child_rate']) }}" min="0" required>
                                <small class="text-muted">A korhatár alatti, kedvezményes játszók óradíja.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Gyerek korhatár (év)</label>
                            <div class="col-sm-8">
                                <input type="number" name="child_age_limit" class="form-control" value="{{ old('child_age_limit', $flipCitySettings['child_age_limit']) }}" min="1" max="18" required>
                                <small class="text-muted">Ennél fiatalabb játszó kapja a gyerek óradíjat (beléptetéskor jelenik meg).</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Minimum perc</label>
                            <div class="col-sm-8">
                                <input type="number" name="min_minutes" class="form-control" value="{{ old('min_minutes', $flipCitySettings['min_minutes']) }}" min="0" max="600" required>
                                <small class="text-muted">Ennyi perc díja mindenképp fizetendő fejenként, akkor is, ha valaki hamarabb távozik. Utána percdíj szerint számol. A kísérő díja ettől független, egyszeri.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Foglalható időszak</label>
                            <div class="col-sm-8">
                                <div class="fc-time-range">
                                    <input type="time" name="booking_from" class="form-control" value="{{ old('booking_from', $flipCitySettings['booking_from']) }}" required aria-label="Foglalható időszak kezdete">
                                    <span>–</span>
                                    <input type="time" name="booking_until" class="form-control" value="{{ old('booking_until', $flipCitySettings['booking_until']) }}" required aria-label="Foglalható időszak vége">
                                </div>
                                <small class="text-muted">A profil oldali foglalásnál csak ezen belüli időpont adható meg, a mai napra pedig csak még el nem múlt időpont.</small>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">QR nyomtatási szöveg</label>
                            <div class="col-sm-8">
                                <textarea name="profile_qr_print_text" class="form-control" rows="3">{{ $flipCitySettings['profile_qr_print_text'] }}</textarea>
                                <small class="text-muted">A profil oldali QR nyomtatási nézetben megjelenő szöveg.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Foglalás megjelenítése profilon</label>
                            <div class="col-sm-8">
                                <select name="show_profile_booking" class="form-control">
                                    <option value="1" {{ $flipCitySettings['show_profile_booking'] ? 'selected' : '' }}>Bekapcsolva</option>
                                    <option value="0" {{ !$flipCitySettings['show_profile_booking'] ? 'selected' : '' }}>Kikapcsolva</option>
                                </select>
                                <small class="text-muted">Ha ki van kapcsolva, a profilon nem lehet foglalni, és a foglalási táblák sem jelennek meg (profil, irányítópult, ügyfél-adatlap).</small>
                            </div>
                        </div>

                        <hr>

                        <h6 class="font-weight-bold text-primary mb-3">Automatikus lezárás</h6>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Automatikus lezárás</label>
                            <div class="col-sm-8">
                                <select name="auto_close_enabled" class="form-control">
                                    <option value="1" {{ old('auto_close_enabled', $flipCitySettings['auto_close']['enabled'] ?? true) ? 'selected' : '' }}>Bekapcsolva</option>
                                    <option value="0" {{ !old('auto_close_enabled', $flipCitySettings['auto_close']['enabled'] ?? true) ? 'selected' : '' }}>Kikapcsolva</option>
                                </select>
                                <small class="text-muted">A rendszer 5 percenként lezárja azokat a belépéseket, amelyeknél a kiléptetés elmaradt. A lezárt belépés nem fizetettként kerül az ügyfél előzményeibe, a lezárásig számolt összeggel.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Lezárás bent töltött idő után (óra)</label>
                            <div class="col-sm-8">
                                <input type="number" name="auto_close_hours" class="form-control" value="{{ old('auto_close_hours', $flipCitySettings['auto_close']['hours'] ?? 3) }}" min="0" max="24" step="0.5" required>
                                <small class="text-muted">A belépéstől ennyi óra után zár le, a díjat eddig az időpontig számolja. 0 esetén nem zár idő alapján.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Napi zárási időpont</label>
                            <div class="col-sm-8">
                                <input type="time" name="auto_close_time" class="form-control" value="{{ old('auto_close_time', $flipCitySettings['auto_close']['time'] ?? '') }}" aria-label="Napi zárási időpont">
                                <small class="text-muted">Ebben az időpontban minden bent maradt belépést lezár (például a nyitvatartás végén). Üresen hagyva nem zár időpont alapján. Ha mindkettő meg van adva, az előbb bekövetkező számít.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Ügyfél tiltása lezáráskor</label>
                            <div class="col-sm-8">
                                <select name="auto_close_block_user" class="form-control">
                                    <option value="1" {{ old('auto_close_block_user', $flipCitySettings['auto_close']['block_user'] ?? true) ? 'selected' : '' }}>Igen, tiltsa le</option>
                                    <option value="0" {{ !old('auto_close_block_user', $flipCitySettings['auto_close']['block_user'] ?? true) ? 'selected' : '' }}>Nem, csak jelölje</option>
                                </select>
                                <small class="text-muted">Tiltás esetén az ügyfél nem léptethető be és a profiljába sem tud belépni, amíg az adatlapján fel nem oldod. A tiltás oka az adatlapon látszik.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Jelzés a következő beléptetéskor</label>
                            <div class="col-sm-8">
                                <select name="auto_close_warn_on_checkin" class="form-control">
                                    <option value="1" {{ old('auto_close_warn_on_checkin', $flipCitySettings['auto_close']['warn_on_checkin'] ?? true) ? 'selected' : '' }}>Bekapcsolva</option>
                                    <option value="0" {{ !old('auto_close_warn_on_checkin', $flipCitySettings['auto_close']['warn_on_checkin'] ?? true) ? 'selected' : '' }}>Kikapcsolva</option>
                                </select>
                                <small class="text-muted">Beléptetéskor rövid figyelmeztetés jelzi, ha az ügyfél előző belépését a rendszer zárta le, és mennyi a rendezetlen összeg.</small>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary px-4">Mentés</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-info">Információ</h6>
                </div>
                <div class="card-body">
                    <p>Ezek a beállítások felülírják a <code>config/flip-city.php</code> fájlban megadott alapértelmezett értékeket.</p>
                    <p>A számlázási modul jelenleg: 
                        @if(config('flip-city.billing_enabled'))
                            <span class="badge badge-success">Aktív</span>
                        @else
                            <span class="badge badge-danger">Inaktív</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="{{ \App\Services\PublicResourcesService::versioned('/packages/flip-city/css/admin/flip-city.css') }}">
@endsection
