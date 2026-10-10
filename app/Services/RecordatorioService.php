<?php

namespace App\Services;

use App\Enums\AccionAuditoria;
use App\Enums\EstadoRecordatorio;
use App\Enums\Permiso;
use App\Jobs\SincronizarRecordatorioGoogleCalendar;
use App\Models\Causa;
use App\Models\Recordatorio;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class RecordatorioService
{
    public function __construct(private AuditoriaService $auditoriaService) {}

    /**
     * @param  array{titulo: string, descripcion: string|null, fecha_hora: CarbonImmutable, recordar_minutos_antes: int|null, user_id: int|null, google_calendar_incluir_creador?: bool}  $attributes
     */
    public function create(Causa $causa, User $actor, array $attributes): Recordatorio
    {
        Gate::forUser($actor)->authorize('view', $causa);
        Gate::forUser($actor)->authorize('create', Recordatorio::class);

        $recordatorio = Recordatorio::query()->create($this->payload($causa, $actor, $attributes) + [
            'created_by' => $actor->id,
            'estado' => EstadoRecordatorio::Pendiente,
        ]);

        $this->auditoriaService->record($recordatorio, AccionAuditoria::RecordatorioCreado, null, $recordatorio->getAttributes());
        $this->syncGoogleCalendar($recordatorio);

        return $recordatorio;
    }

    /**
     * @param  array{titulo: string, descripcion: string|null, fecha_hora: CarbonImmutable, recordar_minutos_antes: int|null, user_id: int|null, google_calendar_incluir_creador?: bool}  $attributes
     */
    public function update(Recordatorio $recordatorio, User $actor, array $attributes): Recordatorio
    {
        Gate::forUser($actor)->authorize('update', $recordatorio);

        $before = $recordatorio->getOriginal();
        $recordatorio->loadMissing('causa');
        $payload = $this->payload($recordatorio->causa, $actor, $attributes);
        $wasReprogrammed = $recordatorio->fecha_hora->notEqualTo($payload['fecha_hora'])
            || $recordatorio->recordar_minutos_antes !== $payload['recordar_minutos_antes'];

        if ($wasReprogrammed && $payload['notificar_en']->isFuture()) {
            $payload['notificado_at'] = null;
        }

        $recordatorio->update($payload);

        $this->auditoriaService->record(
            $recordatorio,
            $wasReprogrammed ? AccionAuditoria::RecordatorioReprogramado : AccionAuditoria::RecordatorioModificado,
            Arr::only($before, array_keys($recordatorio->getChanges())),
            $recordatorio->getChanges(),
        );
        $this->syncGoogleCalendar($recordatorio);

        return $recordatorio;
    }

    public function complete(Recordatorio $recordatorio, User $actor): void
    {
        Gate::forUser($actor)->authorize('update', $recordatorio);
        $before = $recordatorio->getOriginal();
        $recordatorio->update(['estado' => EstadoRecordatorio::Completado]);
        $this->auditoriaService->record($recordatorio, AccionAuditoria::RecordatorioCompletado, $before, $recordatorio->getChanges());
        $this->syncGoogleCalendar($recordatorio);
    }

    public function cancel(Recordatorio $recordatorio, User $actor): void
    {
        Gate::forUser($actor)->authorize('cancel', $recordatorio);
        $before = $recordatorio->getOriginal();
        $recordatorio->update(['estado' => EstadoRecordatorio::Cancelado]);
        $this->auditoriaService->record($recordatorio, AccionAuditoria::RecordatorioCancelado, $before, $recordatorio->getChanges());
        $this->syncGoogleCalendar($recordatorio);
    }

    /**
     * @param  array{titulo: string, descripcion: string|null, fecha_hora: CarbonImmutable, recordar_minutos_antes: int|null, user_id: int|null, google_calendar_incluir_creador?: bool}  $attributes
     * @return array{causa_id: int, user_id: int, titulo: string, descripcion: string|null, fecha_hora: CarbonImmutable, recordar_minutos_antes: int|null, notificar_en: CarbonImmutable, google_calendar_incluir_creador: bool}
     */
    private function payload(Causa $causa, User $actor, array $attributes): array
    {
        $recipientId = $attributes['user_id'] ?? $causa->responsable_id ?? $actor->id;

        if ($recipientId !== ($causa->responsable_id ?? $actor->id) && ! $actor->can(Permiso::RecordatoriosAsignar->value)) {
            throw new AuthorizationException;
        }

        $recipient = User::query()->findOrFail($recipientId);

        if (! Gate::forUser($recipient)->allows('view', $causa)) {
            throw ValidationException::withMessages([
                'userId' => 'El usuario seleccionado no tiene acceso a esta causa.',
            ]);
        }

        $minutes = $attributes['recordar_minutos_antes'] ?? 0;

        return [
            'causa_id' => $causa->id,
            'user_id' => $recipient->id,
            'titulo' => $attributes['titulo'],
            'descripcion' => $attributes['descripcion'],
            'fecha_hora' => $attributes['fecha_hora'],
            'recordar_minutos_antes' => $attributes['recordar_minutos_antes'],
            'notificar_en' => $attributes['fecha_hora']->subMinutes($minutes),
            'google_calendar_incluir_creador' => $attributes['google_calendar_incluir_creador'] ?? false,
        ];
    }

    private function syncGoogleCalendar(Recordatorio $recordatorio): void
    {
        if (config('services.google_calendar.enabled')) {
            SincronizarRecordatorioGoogleCalendar::dispatch($recordatorio->id)->afterCommit();
        }
    }
}
