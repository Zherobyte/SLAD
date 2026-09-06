<?php

use App\Notifications\RecordatorioPendienteNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    /** @return Collection<int, \Illuminate\Notifications\DatabaseNotification> */
    #[Computed]
    public function notifications(): Collection
    {
        return auth()->user()
            ->notifications()
            ->where('type', RecordatorioPendienteNotification::class)
            ->latest()
            ->limit(8)
            ->get();
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()
            ->unreadNotifications()
            ->where('type', RecordatorioPendienteNotification::class)
            ->count();
    }

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->first();

        if ($notification === null) {
            throw new ModelNotFoundException;
        }

        $notification->markAsRead();
        unset($this->notifications, $this->unreadCount);
    }

    public function markAllAsRead(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', RecordatorioPendienteNotification::class)
            ->update(['read_at' => now()]);

        unset($this->notifications, $this->unreadCount);
    }
}; ?>

<div wire:poll.60s class="relative">
    <flux:dropdown position="bottom" align="end">
        <flux:button variant="ghost" icon="bell" square aria-label="Notificaciones">
            @if ($this->unreadCount > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-bold leading-4 text-white">{{ min($this->unreadCount, 99) }}</span>
            @endif
        </flux:button>
        <flux:menu class="w-80">
            <div class="flex items-center justify-between gap-3 px-2 py-2">
                <flux:heading size="sm">Recordatorios</flux:heading>
                @if ($this->unreadCount > 0)
                    <flux:button size="sm" variant="ghost" wire:click="markAllAsRead">Marcar todo leído</flux:button>
                @endif
            </div>
            <flux:menu.separator />
            @forelse ($this->notifications as $notification)
                <flux:menu.item wire:key="notification-{{ $notification->id }}" :href="$notification->data['url'] ?? route('dashboard')" wire:click="markAsRead('{{ $notification->id }}')" wire:navigate>
                    <div class="grid gap-1 whitespace-normal {{ $notification->read_at === null ? 'font-semibold' : '' }}">
                        <span>{{ $notification->data['mensaje'] ?? 'Recordatorio pendiente' }}</span>
                        <span class="text-xs font-normal text-zinc-500">{{ $notification->data['causa_numero'] ?? 'Causa' }} · {{ $notification->created_at->format('d-m H:i') }}</span>
                    </div>
                </flux:menu.item>
            @empty
                <div class="px-3 py-6 text-center text-sm text-zinc-500">No tienes recordatorios notificados.</div>
            @endforelse
        </flux:menu>
    </flux:dropdown>
</div>
