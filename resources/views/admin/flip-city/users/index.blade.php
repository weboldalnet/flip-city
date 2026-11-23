@extends("admin.layouts.layout")

@section('title', 'Felhasználók - Flip-City')
@section('wnSajatHibalista', '1')

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flip-City – Felhasználók</h1>
        <div>
            <a href="{{ route('flip-city.admin.dashboard') }}" class="btn btn-sm btn-secondary shadow-sm mr-2">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> Dashboard
            </a>
            <button class="btn btn-sm btn-primary shadow-sm" data-toggle="modal" data-target="#addUserModal">
                <i class="fas fa-user-plus fa-sm mr-1"></i> Új Ügyfél
            </button>
        </div>
    </div>

    @if(session('success') && !session('wn_toast'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if(session('error') && !session('wn_toast'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <form method="GET" action="{{ route('flip-city.admin.users.index') }}" class="form-inline">
                <input type="text" name="search" class="form-control form-control-sm mr-2"
                       placeholder="Keresés (név, email, telefon)..."
                       value="{{ request('search') }}" style="min-width:240px">
                <select name="status" class="form-control form-control-sm mr-2">
                    <option value="">Minden státusz</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktív</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inaktív</option>
                    <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Tiltott</option>
                </select>
                <button type="submit" class="btn btn-sm btn-primary mr-1">
                    <i class="fas fa-search fa-sm"></i> Szűrés
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('flip-city.admin.users.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times fa-sm"></i> Visszaállítás
                    </a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Név</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th class="text-right">Egyenleg</th>
                            <th>Belépések</th>
                            <th>Státusz</th>
                            <th>Regisztrált</th>
                            <th>Műveletek</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td class="text-muted small">{{ $user->id }}</td>
                            <td>
                                <a href="{{ route('flip-city.admin.users.show', $user->id) }}" class="font-weight-bold text-dark">
                                    {{ $user->name }}
                                </a>
                            </td>
                            <td>{{ $user->email ?? '–' }}</td>
                            <td>{{ $user->phone ?? '–' }}</td>
                            <td class="text-right">
                                <span class="{{ $user->balance > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ number_format($user->balance, 0, ',', ' ') }} Ft
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $user->entries_count }}</span>
                                @php $failedCount = $user->failed_entries_count; @endphp
                                @if($failedCount > 0)
                                    <span class="badge badge-danger ml-1" title="Meghiúsult belépések száma">{{ $failedCount }}</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_blocked)
                                    <span class="badge badge-danger">Tiltott</span>
                                @elseif(!$user->is_active)
                                    <span class="badge badge-warning">Inaktív</span>
                                @else
                                    <span class="badge badge-success">Aktív</span>
                                @endif
                                @if($user->card_registered)
                                    <span class="badge badge-info ml-1" title="Bankkártya rögzítve">
                                        <i class="fas fa-credit-card"></i>
                                    </span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $user->created_at->format('Y.m.d') }}</td>
                            <td class="text-nowrap">
                                <div class="fc-actions">
                                    <button type="button" class="fc-act fc-act-go checkin-manual-btn"
                                            data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                            data-notice="{{ $user->checkinNotice() }}"
                                            @if(!$user->is_active || $user->is_blocked) disabled @endif
                                            title="{{ !$user->is_active || $user->is_blocked ? 'Letiltott vagy inaktív ügyfél nem léptethető be' : 'Beléptetés (manuális)' }}">
                                        <i class="fas fa-sign-in-alt"></i><span>Beléptet</span>
                                    </button>
                                    <a href="{{ route('flip-city.admin.users.show', $user->id) }}"
                                       class="fc-act" title="Részletek" aria-label="Részletek">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('flip-city.admin.users.edit', $user->id) }}"
                                       class="fc-act" title="Szerkesztés" aria-label="Szerkesztés">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <form action="{{ route('flip-city.admin.users.toggle-active', $user->id) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="fc-act {{ $user->is_active ? 'fc-act-warn' : 'fc-act-ok' }}"
                                                title="{{ $user->is_active ? 'Deaktiválás' : 'Aktiválás' }}"
                                                aria-label="{{ $user->is_active ? 'Deaktiválás' : 'Aktiválás' }}">
                                            <i class="fas {{ $user->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('flip-city.admin.users.toggle-blocked', $user->id) }}"
                                          method="POST">
                                        @csrf
                                        <button type="submit"
                                                class="fc-act {{ $user->is_blocked ? 'fc-act-ok' : 'fc-act-danger' }}"
                                                title="{{ $user->is_blocked ? 'Tiltás feloldása' : 'Letiltás' }}"
                                                aria-label="{{ $user->is_blocked ? 'Tiltás feloldása' : 'Letiltás' }}">
                                            <i class="fas {{ $user->is_blocked ? 'fa-lock-open' : 'fa-lock' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                Nincs találat a megadott feltételekre.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    Összesen: {{ $users->total() }} felhasználó
                </small>
                {{ $users->appends(request()->query())->links('pagination::bootstrap-4') }}
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
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
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email <span class="text-muted small">(opcionális)</span></label>
                        <input type="email" name="email" class="form-control">
                        <small class="form-text text-muted">Ha megadja, jelszó-beállító emailt küldünk.</small>
                    </div>
                    <div class="form-group">
                        <label>Telefonszám</label>
                        <input type="text" name="phone" class="form-control" placeholder="+36 30 ...">
                    </div>
                    <hr>
                    <h6 class="font-weight-bold">Számlázási adatok <span class="text-danger">*</span></h6>
                    <div class="form-group row">
                        <div class="col-sm-4">
                            <label>Irányítószám <span class="text-danger">*</span></label>
                            <input type="text" name="billing_zip" class="form-control" required placeholder="1234">
                        </div>
                        <div class="col-sm-8">
                            <label>Város <span class="text-danger">*</span></label>
                            <input type="text" name="billing_city" class="form-control" required placeholder="Budapest">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Cím (utca, házszám) <span class="text-danger">*</span></label>
                        <input type="text" name="billing_address" class="form-control" required placeholder="Példa utca 1.">
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

<!-- Checkin Manual Modal -->
<div class="modal fade" id="checkinManualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Beléptetés</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="checkin_manual_form" action="{{ route('flip-city.admin.entries.store-manual') }}" method="POST"
                  data-manual-url="{{ route('flip-city.admin.entries.store-manual') }}"
                  data-booking-url="{{ route('flip-city.admin.entries.store-from-booking') }}">
                @csrf
                <input type="hidden" name="user_id" id="checkin_user_id">
                <div class="modal-body">
                    <p id="checkin_user_name" class="font-weight-bold mb-3"></p>
                    <p id="checkin_notice" class="alert alert-warning small py-2 mb-3 d-none"></p>
                    @include('flip-city::admin.flip-city.partials.checkin-counts', ['prefix' => 'checkin'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Mégse</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-play mr-1"></i> Beléptetés
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
    <script src="{{ \App\Services\PublicResourcesService::versioned('/packages/flip-city/js/admin/flip-city.js') }}"></script>
@endpush

<link rel="stylesheet" href="{{ \App\Services\PublicResourcesService::versioned('/packages/flip-city/css/admin/flip-city.css') }}">
@endsection
