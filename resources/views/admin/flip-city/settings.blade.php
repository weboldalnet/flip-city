@extends('admin.layouts.layout')

@section('title', 'Beállítások - Flip-City')

@section('content')
<div class="container-fluid">
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
                                <input type="number" name="default_rate" class="form-control" value="{{ $flipCitySettings['default_rate'] }}" min="0" required>
                                <small class="text-muted">A beléptetéskor használt alapértelmezett óradíj.</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label font-weight-bold">Kísérő/fő ára (Ft)</label>
                            <div class="col-sm-8">
                                <input type="number" name="companion_price" class="form-control" value="{{ $flipCitySettings['companion_price'] }}" min="0" required>
                                <small class="text-muted">Egyszeri díj kísérőnként belépéskor.</small>
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
                                <small class="text-muted">Ha ki van kapcsolva, a profil oldalon nem látszanak a foglalások.</small>
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
@endsection
