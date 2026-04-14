<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Weboldalnet\FlipCity\Mail\FlipCityMail;
use Weboldalnet\FlipCity\Models\User;
use Weboldalnet\FlipCity\Services\QRCodeService;

class UserController extends FlipCityAdminController
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true)->where('is_blocked', false);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->input('status') === 'blocked') {
                $query->where('is_blocked', true);
            }
        }

        $users = $query->withCount('entries')->orderBy('created_at', 'desc')->paginate(20);

        return view('flip-city::admin.flip-city.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $entries = $user->entries()->orderBy('created_at', 'desc')->take(20)->get();
        $bookings = $user->bookings()->orderBy('created_at', 'desc')->take(10)->get();

        return view('flip-city::admin.flip-city.users.show', compact('user', 'entries', 'bookings'));
    }

    public function edit(User $user)
    {
        return view('flip-city::admin.flip-city.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'nullable|email|max:255|unique:flip_city_users,email,' . $user->id,
            'phone'           => 'nullable|string|max:50',
            'billing_details' => 'nullable|string',
            'balance'         => 'nullable|numeric|min:0',
            'card_registered' => 'boolean',
        ]);

        $user->update([
            'name'            => $validated['name'],
            'email'           => $validated['email'] ?? null,
            'phone'           => $validated['phone'] ?? null,
            'billing_details' => $validated['billing_details'] ?? null,
            'balance'         => $validated['balance'] ?? $user->balance,
            'card_registered' => $request->boolean('card_registered'),
        ]);

        return redirect()->route('flip-city.admin.users.show', $user->id)
            ->with('success', 'Felhasználó adatai sikeresen frissítve.');
    }

    public function toggleActive(User $user)
    {
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'aktiválva' : 'deaktiválva';

        return redirect()->back()->with('success', "Felhasználó {$status}.");
    }

    public function toggleBlocked(User $user)
    {
        $user->is_blocked = !$user->is_blocked;
        $user->save();

        $status = $user->is_blocked ? 'letiltva' : 'feloldva';

        return redirect()->back()->with('success', "Felhasználó {$status}.");
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('flip-city.admin.users.index')
            ->with('success', 'Felhasználó törölve.');
    }

    public function sendPasswordReset(User $user)
    {
        if (!$user->email) {
            return redirect()->back()->with('error', 'Ennek a felhasználónak nincs email címe.');
        }

        $mailData = [
            'subject'     => 'Jelszó visszaállítás',
            'success_res' => 'Jelszó visszaállítási kérelem',
            'desc'        => 'Kérjük, állítsa be jelszavát az alábbi linken: <br><a href="' . url('/password/reset/' . Str::random(60)) . '">Jelszó beállítása</a>',
        ];

        Mail::to($user->email)->send(new FlipCityMail($user, $mailData));

        return redirect()->back()->with('success', 'Jelszó visszaállítási email elküldve.');
    }

    public function generateQRCode(User $user)
    {
        if (!$user->qr_code_token) {
            $user->qr_code_token = Str::uuid()->toString();
        }

        $user->qr_code_svg = QRCodeService::generateQRCode($user->qr_code_token);
        $user->save();

        return redirect()->back()->with('success', 'QR kód sikeresen újragenerálva.');
    }
}
