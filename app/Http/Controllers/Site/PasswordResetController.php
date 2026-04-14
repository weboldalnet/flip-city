<?php

namespace Weboldalnet\FlipCity\Http\Controllers\Site;

use App\Http\Controllers\Site\SiteExtendedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Weboldalnet\FlipCity\Models\User;
use Weboldalnet\FlipCity\Mail\FlipCityMail;

class PasswordResetController extends SiteExtendedController
{
    public function showForgotPasswordForm()
    {
        return view("flip-city::site.flip-city.forgot-password");
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(["email" => "required|email|exists:flip_city_users,email"]);

        $token = Str::random(64);

        DB::table("password_reset_tokens")->updateOrInsert(
            ["email" => $request->email],
            [
                "email" => $request->email,
                "token" => $token,
                "created_at" => Carbon::now()
            ]
        );

        $user = User::where("email", $request->email)->first();

        $mailData = [
            "subject" => "Jelszó visszaállítási kérelem",
            "success_res" => "Jelszó visszaállítása",
            "desc" => "Az alábbi gombra kattintva megváltoztathatja jelszavát:<br><br>" .
                     "<a href=\"" . route("flip-city.password.reset", $token) . "\" style=\"background: #007bff; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 5px;\">Jelszó megváltoztatása</a>",
        ];

        Mail::to($request->email)->send(new FlipCityMail($user, $mailData));

        return back()->with("success", "A jelszó visszaállító linket elküldtük az e-mail címére.");
    }

    public function showResetForm($token)
    {
        return view("flip-city::site.flip-city.reset-password", ["token" => $token]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            "token" => "required",
            "email" => "required|email|exists:flip_city_users,email",
            "password" => "required|min:8|confirmed",
        ]);

        $reset = DB::table("password_reset_tokens")
            ->where("email", $request->email)
            ->where("token", $request->token)
            ->first();

        if (!$reset) {
            return back()->withErrors(["email" => "Érvénytelen token vagy e-mail cím."]);
        }

        if (Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            DB::table("password_reset_tokens")->where("email", $request->email)->delete();
            return back()->withErrors(["email" => "A jelszó visszaállító link lejárt."]);
        }

        $user = User::where("email", $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->is_active = true;
        $user->activated_at = (new \DateTimeImmutable())->format( "Y-m-d H:i:s");
        $user->save();

        DB::table("password_reset_tokens")->where("email", $request->email)->delete();

        return redirect()->route("flip-city.login.show")->with("success", "A jelszava sikeresen megváltozott! Most már bejelentkezhet.");
    }
}
