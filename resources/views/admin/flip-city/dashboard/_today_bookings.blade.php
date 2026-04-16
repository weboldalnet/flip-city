<div class="card shadow mb-4 mt-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Mai nem beléptetett foglalások</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Időpont</th>
                        <th>Név</th>
                        <th>Létszám</th>
                        <th>Megjegyzés</th>
                        <th>Művelet</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($todayBookings as $booking)
                    <tr>
                        <td class="align-middle"><strong>{{ $booking->booking_time }}</strong></td>
                        <td class="align-middle">{{ $booking->user->name }}</td>
                        <td class="align-middle">{{ $booking->guest_count }} fő</td>
                        <td class="align-middle small text-muted">{{ $booking->comments ?? '-' }}</td>
                        <td class="align-middle">
                            <button class="btn btn-sm btn-success checkin-manual-btn" 
                                    data-id="{{ $booking->user_id }}" 
                                    data-name="{{ $booking->user->name }}"
                                    data-guests="{{ $booking->guest_count }}">
                                <i class="fas fa-sign-in-alt"></i> Beléptetés
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Nincs mára több függő foglalás.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
