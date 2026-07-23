@extends('site.layouts.layout')

@section('title', 'Összes belépés - Flip-City')
@section('og_title', 'Összes belépés - Flip-City')

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
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Dátum</th>
                                <th>Időtartam</th>
                                <th class="text-center">Létszám</th>
                                <th class="text-center">Kis.</th>
                                <th class="text-right">Összeg</th>
                                <th>Státusz</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($entries as $entry)
                            <tr class="{{ $entry->is_failed ? 'table-danger' : '' }}">
                                <td>
                                    <div class="font-weight-bold">{{ $entry->start_time->format('Y.m.d') }}</div>
                                    <div class="small text-muted">{{ $entry->start_time->format('H:i') }} - {{ $entry->end_time ? $entry->end_time->format('H:i') : '...' }}</div>
                                </td>
                                <td>
                                    @if($entry->end_time)
                                        {{ ceil($entry->start_time->diffInMinutes($entry->end_time)) }} perc
                                    @else
                                        <span class="text-primary">Folyamatban...</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $entry->guest_count }} fő</td>
                                <td class="text-center">{{ $entry->companions_count }} fő</td>
                                <td class="text-right font-weight-bold">
                                    {{ number_format($entry->total_cost ?: $entry->calculateCurrentCost(), 0, ',', ' ') }} Ft
                                </td>
                                <td>
                                    @if($entry->is_failed)
                                        <span class="badge badge-danger">Meghiúsult</span>
                                    @elseif(!$entry->end_time)
                                        <span class="badge badge-primary">Aktív</span>
                                    @else
                                        <span class="badge badge-success">Lezárva</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
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
                {{ $entries->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .flipcity-entries .table th { border-top: none; }
    .flipcity-entries .badge { font-weight: 500; padding: 0.4em 0.8em; }
</style>
@endsection
