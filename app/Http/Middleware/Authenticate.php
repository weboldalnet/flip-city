<?php

namespace Weboldalnet\FlipCity\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        $user = $request->user();
        if ($user && ($user->is_blocked || !$user->is_active)) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('flip-city.login.show')
                ->with('error', $user->is_blocked ? 'A fiókja le van tiltva.' : 'A fiókja jelenleg nem aktív.');
        }

        return $next($request);
    }

    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('flip-city.login.show');
    }
}
