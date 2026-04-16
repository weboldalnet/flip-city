@extends("admin.layouts.layout")

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-user-edit mr-2 text-warning"></i>Felhasználó Szerkesztése
        </h1>
        <div>
            <a href="{{ route('flip-city.admin.users.show', $user->id) }}" class="btn btn-sm btn-secondary shadow-sm mr-1">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> Vissza
            </a>
            <a href="{{ route('flip-city.admin.users.index') }}" class="btn btn-sm btn-outline-secondary shadow-sm">
                <i class="fas fa-list fa-sm mr-1"></i> Lista
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    <div class="row">
        <div class="col-xl-7 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        Adatok szerkesztése – #{{ $user->id }} – {{ $user->name }}
                    </h6>
                </div>
                <form action="{{ route('flip-city.admin.users.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Név <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                       value="{{ old('name', $user->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                       value="{{ old('email', $user->email) }}">
                                <small class="form-text text-muted">Ha nincs email, üresen hagyható.</small>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Telefonszám</label>
                            <div class="col-sm-9">
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>
                        <h6 class="font-weight-bold mb-3">Számlázási adatok</h6>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Irányítószám <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="billing_zip" class="form-control @error('billing_zip') is-invalid @enderror"
                                       value="{{ old('billing_zip', $user->billing_zip) }}" required>
                                @error('billing_zip')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Város <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="billing_city" class="form-control @error('billing_city') is-invalid @enderror"
                                       value="{{ old('billing_city', $user->billing_city) }}" required>
                                @error('billing_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Cím <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="billing_address" class="form-control @error('billing_address') is-invalid @enderror"
                                       value="{{ old('billing_address', $user->billing_address) }}" required>
                                @error('billing_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Megjegyzés</label>
                            <div class="col-sm-9">
                                <textarea name="billing_details" class="form-control @error('billing_details') is-invalid @enderror"
                                          rows="2">{{ old('billing_details', $user->billing_details) }}</textarea>
                                @error('billing_details')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Egyenleg (Ft)</label>
                            <div class="col-sm-9">
                                <input type="number" name="balance" class="form-control @error('balance') is-invalid @enderror"
                                       value="{{ old('balance', $user->balance) }}" min="0" step="1">
                                <small class="form-text text-muted">Aktuális előre fizetett egyenleg forintban.</small>
                                @error('balance')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">Bankkártya</label>
                            <div class="col-sm-9 d-flex align-items-center">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="card_registered"
                                           name="card_registered" value="1"
                                           {{ old('card_registered', $user->card_registered) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="card_registered">
                                        Bankkártya rögzítve
                                    </label>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <a href="{{ route('flip-city.admin.users.show', $user->id) }}" class="btn btn-secondary">
                            Mégse
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Mentés
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Oldalsáv: gyors állapot kezelés -->
        <div class="col-xl-5 col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Állapot kezelés</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="font-weight-bold">Fiók aktivitás</span><br>
                                <small class="text-muted">Inaktív fiók nem tud belépni a rendszerbe.</small>
                            </div>
                            @if($user->is_active)
                                <span class="badge badge-success">Aktív</span>
                            @else
                                <span class="badge badge-warning">Inaktív</span>
                            @endif
                        </div>
                        <form action="{{ route('flip-city.admin.users.toggle-active', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-block {{ $user->is_active ? 'btn-outline-secondary' : 'btn-success' }}">
                                <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check-circle' }} mr-1"></i>
                                {{ $user->is_active ? 'Deaktiválás' : 'Aktiválás' }}
                            </button>
                        </form>
                    </div>

                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div>
                                <span class="font-weight-bold">Letiltás</span><br>
                                <small class="text-muted">Tiltott felhasználó nem léphet be.</small>
                            </div>
                            @if($user->is_blocked)
                                <span class="badge badge-danger">Tiltott</span>
                            @else
                                <span class="badge badge-success">Nincs tiltva</span>
                            @endif
                        </div>
                        <form action="{{ route('flip-city.admin.users.toggle-blocked', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-block {{ $user->is_blocked ? 'btn-outline-success' : 'btn-danger' }}">
                                <i class="fas {{ $user->is_blocked ? 'fa-lock-open' : 'fa-lock' }} mr-1"></i>
                                {{ $user->is_blocked ? 'Tiltás feloldása' : 'Letiltás' }}
                            </button>
                        </form>
                    </div>

                    @if($user->email)
                    <div class="p-3 bg-light rounded">
                        <div class="mb-2">
                            <span class="font-weight-bold">Jelszó visszaállítás</span><br>
                            <small class="text-muted">Küld egy jelszó-beállító emailt a felhasználónak.</small>
                        </div>
                        <form action="{{ route('flip-city.admin.users.send-password-reset', $user->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-block btn-outline-primary">
                                <i class="fas fa-envelope mr-1"></i> Email küldése
                            </button>
                        </form>
                    </div>
                    @else
                    <div class="p-3 bg-light rounded text-muted small">
                        <i class="fas fa-info-circle mr-1"></i>
                        Jelszó-reset email nem küldhető, mert a felhasználóhoz nincs email cím rögzítve.
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow border-danger">
                <div class="card-header py-3 bg-danger text-white">
                    <h6 class="m-0 font-weight-bold">Veszélyes zóna</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">A felhasználó törlése végleges és visszafordíthatatlan. Minden kapcsolódó adat is törlődik.</p>
                    <button type="button" class="btn btn-danger btn-block btn-sm"
                            data-toggle="modal" data-target="#deleteModal">
                        <i class="fas fa-trash mr-1"></i> Felhasználó törlése
                    </button>
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
                <p class="text-danger small">Ez a művelet nem visszavonható!</p>
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
