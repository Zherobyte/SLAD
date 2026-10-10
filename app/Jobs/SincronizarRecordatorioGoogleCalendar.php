<?php

namespace App\Jobs;

use App\Models\Recordatorio;
use App\Services\GoogleCalendarService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Log;
use Throwable;

class SincronizarRecordatorioGoogleCalendar implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(public int $recordatorioId) {}

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $googleCalendarService): void
    {
        $recordatorio = Recordatorio::query()
            ->with(['causa:id,numero_causa,nombre', 'usuario:id,name,email', 'creador:id,name,email'])
            ->find($this->recordatorioId);

        if ($recordatorio === null) {
            return;
        }

        $googleCalendarService->sync($recordatorio);
    }

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("recordatorio-google-calendar-{$this->recordatorioId}"))->releaseAfter(5)->expireAfter(60)];
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('No fue posible sincronizar el recordatorio con Google Calendar.', [
            'recordatorio_id' => $this->recordatorioId,
            'exception' => $exception?->getMessage(),
        ]);
    }
}
