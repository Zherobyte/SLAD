<?php

namespace App\Http\Controllers;

use App\Enums\RolUsuario;
use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoogleCalendarOAuthController extends Controller
{
    public function show(Request $request): View
    {
        $this->admin($request);

        return view('pages.admin.google-calendar', ['connection' => GoogleCalendarConnection::query()->with('connector')->first()]);
    }

    public function redirect(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $this->admin($request);
        $state = $oauth->state();
        $request->session()->put('google_calendar_state', $state);

        return redirect()->away($oauth->authorizationUrl($state));
    }

    public function callback(Request $request, GoogleCalendarOAuthService $oauth): RedirectResponse
    {
        $user = $this->admin($request);
        abort_unless(hash_equals((string) $request->session()->pull('google_calendar_state'), (string) $request->query('state')), 403);
        $request->validate(['code' => ['required', 'string']]);
        $oauth->connect($request->string('code')->toString(), $user);

        return to_route('admin.google-calendar.show')->with('status', 'Cuenta conectada correctamente.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->admin($request);
        GoogleCalendarConnection::query()->delete();

        return to_route('admin.google-calendar.show')->with('status', 'Conexión eliminada.');
    }

    private function admin(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasRole(RolUsuario::Administrador->value), 403);

        return $user;
    }
}
