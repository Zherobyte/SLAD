<x-layouts::app title="Google Calendar">
    <main class="mx-auto w-full max-w-3xl p-6 lg:p-10">
        <div class="grid gap-6">
            <div><flux:heading size="xl">Google Calendar</flux:heading><flux:text>Conecta la cuenta institucional para sincronizar recordatorios.</flux:text></div>
            @if (session('status')) <flux:callout variant="success" icon="check-circle" :text="session('status')" /> @endif
            <flux:card class="grid gap-5">
                @if ($connection)
                    <div class="grid gap-1"><flux:heading size="lg">Cuenta conectada</flux:heading><flux:text>{{ $connection->google_email }} · {{ $connection->calendar_name }}</flux:text><flux:text class="text-sm">Conectada por {{ $connection->connector->name }} el {{ $connection->connected_at->format('d-m-Y H:i') }}.</flux:text></div>
                    <div class="flex gap-3"><flux:button :href="route('admin.google-calendar.connect')" variant="primary" icon="arrow-path">Reconectar</flux:button><form method="POST" action="{{ route('admin.google-calendar.destroy') }}">@csrf @method('DELETE')<flux:button type="submit" variant="danger" icon="link-slash">Desconectar</flux:button></form></div>
                @else
                    <div class="grid gap-1"><flux:heading size="lg">Sin cuenta conectada</flux:heading><flux:text>La autorización se guarda cifrada; el token nunca se muestra.</flux:text></div>
                    <flux:button :href="route('admin.google-calendar.connect')" variant="primary" icon="calendar-days">Conectar Google Calendar</flux:button>
                @endif
            </flux:card>
        </div>
    </main>
</x-layouts::app>
