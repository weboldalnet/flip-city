@extends("admin.layouts.layout")

@section("content")
<div class="container-fluid flip-city-admin">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Flip-City – Belépési előzmények</h1>
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
            <h6 class="m-0 font-weight-bold text-primary">Összes belépés</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Vendég</th>
                            <th>Belépés</th>
                            <th>Kilépés</th>
                            <th class="text-center">Fő</th>
                            <th class="text-center">Végösszeg</th>
                            <th class="text-center">Státusz</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="text-muted small">{{ $entry->id }}</td>
                            <td>
                                @if($entry->user)
                                    <a href="{{ route('flip-city.admin.users.show', $entry->user_id) }}"
                                       class="font-weight-bold text-dark">
                                        {{ $entry->user->name }}
                                    </a>
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td>{{ $entry->start_time->format('Y.m.d H:i') }}</td>
                            <td>
                                @if($entry->end_time)
                                    {{ $entry->end_time->format('Y.m.d H:i') }}
                                    @if($entry->is_auto_closed)
                                        <span class="badge badge-warning ml-1" title="Automatikusan lezárva">AUTO</span>
                                    @endif
                                @else
                                    <span class="badge badge-success">Aktív</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $entry->guest_count }}</td>
                            <td class="text-center font-weight-bold">
                                @if($entry->total_cost !== null)
                                    {{ number_format($entry->total_cost, 0, ',', ' ') }} Ft
                                @else
                                    <span class="text-muted">–</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if(!$entry->end_time)
                                    <span class="badge badge-success">Aktív</span>
                                @elseif($entry->is_auto_closed)
                                    <span class="badge badge-warning">Auto-lezárt</span>
                                @else
                                    <span class="badge badge-secondary">Lezárt</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-history fa-2x mb-2 d-block"></i>
                                Nincs rögzített belépés.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($entries->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Összesen: {{ $entries->total() }} belépés</small>
                {{ $entries->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<link rel="stylesheet" href="{{ asset('packages/flip-city/css/admin/flip-city.css') }}">
@endsection
