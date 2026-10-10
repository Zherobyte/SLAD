<?php

namespace App\Services;

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleCalendarOAuthService
{
    public function state(): string
    {
        return Str::random(64);
    }

    public function authorizationUrl(string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query(['client_id' => config('services.google_calendar.client_id'), 'redirect_uri' => route('admin.google-calendar.callback'), 'response_type' => 'code', 'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/userinfo.email', 'access_type' => 'offline', 'prompt' => 'consent', 'state' => $state]);
    }

    public function connect(string $code, User $user): void
    {
        $token = Http::asForm()->acceptJson()->post('https://oauth2.googleapis.com/token', ['code' => $code, 'client_id' => config('services.google_calendar.client_id'), 'client_secret' => config('services.google_calendar.client_secret'), 'redirect_uri' => route('admin.google-calendar.callback'), 'grant_type' => 'authorization_code'])->throw()->json();
        $refresh = $token['refresh_token'] ?? null;
        $access = $token['access_token'] ?? null;
        if (! is_string($refresh) || ! is_string($access)) {
            throw new RuntimeException('Google no devolvió las credenciales requeridas.');
        }
        $email = Http::withToken($access)->get('https://www.googleapis.com/oauth2/v3/userinfo')->throw()->json('email');
        if (! is_string($email)) {
            throw new RuntimeException('Google no devolvió el correo de la cuenta.');
        }
        GoogleCalendarConnection::query()->updateOrCreate(['id' => GoogleCalendarConnection::query()->value('id')], ['google_email' => $email, 'calendar_id' => 'primary', 'calendar_name' => 'Calendario principal', 'refresh_token' => $refresh, 'connected_by' => $user->id, 'connected_at' => now()]);
    }
}
