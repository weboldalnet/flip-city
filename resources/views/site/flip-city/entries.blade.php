@extends('site.layouts.layout')

@section('title', 'Összes belépés - Flip-City')
@section('og_title', 'Összes belépés - Flip-City')

@section('extendCSS')
    @if($flipCitySettings['css_published'])
        <link rel="stylesheet" href="{{ \App\Services\PublicResourcesService::versioned('/packages/flip-city/css/site/flip-city.css') }}">
    @endif
@endsection

@section('content')
<div class="flipcity flipcity-entries">
    <div class="container py-5" style="min-height: 600px">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">Összes belépés</h1>
            <a href="{{ route('flip-city.profile') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Vissza a profilomra
            </a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 fc-entries-table">
                        <thead>
                            <tr>
                                <th>Dátum</th>
                                <th>Időtartam</th>
                                <th class="text-center">Fő</th>
                                <th class="text-center">Gyerek</th>
                                <th class="text-center">Kísérő</th>
                                <th class="text-right">Összeg</th>
                                <th class="text-right">Állapot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                            <tr class="{{ $entry->is_failed ? 'is-failed' : (!$entry->end_time ? 'is-active' : '') }}">
                                <td data-label="Dátum">
                                    <span class="fc-entry-date">{{ $entry->start_time->format('Y.m.d.') }}</span>
                                    <span class="fc-entry-time">{{ $entry->start_time->format('H:i') }} – {{ $entry->end_time ? $entry->end_time->format('H:i') : '…' }}</span>
                                </td>
                                <td data-label="Időtartam">
                                    @if($entry->end_time)
                                        @php($minutes = (int) ceil($entry->start_time->diffInMinutes($entry->end_time)))
                                        {{ $minutes >= 60 ? intdiv($minutes, 60) . ' ó ' . ($minutes % 60 ? ($minutes % 60) . ' perc' : '') : $minutes . ' perc' }}
                                    @else
                                        <span class="fc-entry-running">Folyamatban</span>
                                    @endif
                                </td>
                                <td data-label="Fő" class="text-center">{{ $entry->guest_count - $entry->children_count }}</td>
                                <td data-label="Gyerek" class="text-center">{{ $entry->children_count }}</td>
                                <td data-label="Kísérő" class="text-center">{{ $entry->companions_count }}</td>
                                <td data-label="Összeg" class="text-right fc-entry-amount">
                                    {{ number_format($entry->total_cost ?: $entry->calculateCurrentCost(), 0, ',', ' ') }} Ft
                                </td>
                                <td data-label="Állapot" class="text-right">
                                    @if($entry->is_failed)
                                        <span class="badge badge-danger">Meghiúsult</span>
                                    @elseif(!$entry->end_time)
                                        <span class="badge badge-primary">Aktív</span>
                                    @elseif($entry->is_auto_closed)
                                        <span class="badge badge-warning" title="A rendszer zárta le a nap végén">Automatikusan lezárva</span>
                                    @else
                                        <span class="badge badge-success">Lezárva</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr class="fc-entry-empty">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-history fa-3x mb-3 d-block"></i>
                                    Még nem volt belépése.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($entries->hasPages())
            <div class="card-footer bg-white">
                {{ $entries->links('pagination::bootstrap-4') }}
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
