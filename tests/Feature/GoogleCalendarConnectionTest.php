<?php

use App\Models\User;

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
