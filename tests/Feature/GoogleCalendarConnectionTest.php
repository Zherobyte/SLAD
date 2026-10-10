<?php

use App\Models\GoogleCalendarConnection;
use App\Models\User;
use App\Services\GoogleCalendarOAuthService;
use Illuminate\Support\Facades\Http;

test('only administrators can manage the institutional Google Calendar connection', function () {
    $administrator = User::factory()->administrador()->create();
    $lawyer = User::factory()->abogado()->create();

    $this->actingAs($administrator)->get(route('admin.google-calendar.show'))->assertSuccessful();
    $this->actingAs($lawyer)->get(route('admin.google-calendar.show'))->assertForbidden();
});

test('administrator is redirected to Google authorization with a protected state', function () {
    config()->set('services.google_calendar.client_id', 'client-id');
    $administrator = User::factory()->administrador()->create();

    $response = $this->actingAs($administrator)->get(route('admin.google-calendar.connect'));

    $response->assertRedirectContains('https://accounts.google.com/o/oauth2/v2/auth');
    expect(session('google_calendar_state'))->toBeString()->not->toBeEmpty();
});

test('stores the institutional Google Calendar connection after authorization', function () {
    config()->set('services.google_calendar.client_id', 'client-id');
    config()->set('services.google_calendar.client_secret', 'client-secret');
    Http::preventStrayRequests();
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
        ]),
        'https://www.googleapis.com/oauth2/v3/userinfo' => Http::response([
            'email' => 'calendar@example.com',
        ]),
    ]);
    $administrator = User::factory()->administrador()->create();

    app(GoogleCalendarOAuthService::class)->connect('authorization-code', $administrator);

    $connection = GoogleCalendarConnection::query()->first();

    $this->assertModelExists($connection);
    expect($connection)
        ->google_email->toBe('calendar@example.com')
        ->refresh_token->toBe('refresh-token')
        ->connected_by->toBe($administrator->id);
});
