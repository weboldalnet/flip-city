<div class="row mt-4">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-danger">Meghiúsult Belépések ({{ $failedEntries->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered mb-0" width="100%" cellspacing="0">
                        <thead class="thead-light">
                            <tr>
                                <th>Név</th>
                                <th>Dátum</th>
                                <th>Belépés/Meghiúsult</th>
                                <th class="text-right">Összeg</th>
                                <th class="text-center">Fő</th>
                                <th class="text-center">Kis.</th>
                                <th>Művelet</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($failedEntries as $entry)
                            <tr>
                                <td class="font-weight-bold">
                                    <a href="{{ route('flip-city.admin.users.show', $entry->user_id) }}">
                                        {{ $entry->user->name ?? '–' }}
                                    </a>
                                </td>
                                <td class="small">{{ $entry->start_time->format('Y.m.d') }}</td>
                                <td>
                                    {{ $entry->start_time->format('H:i') }} - 
                                    <span class="text-danger">{{ $entry->end_time ? $entry->end_time->format('H:i') : $entry->updated_at->format('H:i') }}</span>
                                </td>
                                <td class="text-right font-weight-bold text-danger">
                                    {{ number_format($entry->total_cost ?: $entry->calculateCurrentCost(), 0, ',', ' ') }} Ft
                                </td>
                                <td class="text-center">{{ $entry->guest_count }}</td>
                                <td class="text-center">{{ $entry->companions_count }}</td>
                                <td>
                                    <form action="{{ route('flip-city.admin.entries.unfail', $entry->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Biztosan visszaállítja ezt a belépést és hozzáadja a napi bevételhez?')">
                                        @csrf
                                        <button type="submit" class="btn btn-xs btn-success">
                                            <i class="fas fa-undo mr-1"></i> Visszaállítás
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Nincs meghiúsult belépés.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
