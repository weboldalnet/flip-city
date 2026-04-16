<div class="card shadow mb-4 mt-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-info">Jövőbeli foglalások</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Dátum</th>
                        <th>Időpont</th>
                        <th>Név</th>
                        <th>Létszám</th>
                        <th>Megjegyzés</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($futureBookings as $booking)
                    <tr>
                        <td class="align-middle">{{ $booking->booking_date }}</td>
                        <td class="align-middle"><strong>{{ $booking->booking_time }}</strong></td>
                        <td class="align-middle">{{ $booking->user->name }}</td>
                        <td class="align-middle">{{ $booking->guest_count }} fő</td>
                        <td class="align-middle small text-muted">{{ $booking->comments ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Nincsenek jövőbeli foglalások.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
