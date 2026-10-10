<?php

namespace App\Models;

use App\Enums\EstadoRecordatorio;
use Carbon\CarbonImmutable;
use Database\Factories\RecordatorioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $causa_id
 * @property int $user_id
 * @property int $created_by
 * @property string $titulo
 * @property string|null $descripcion
 * @property CarbonImmutable $fecha_hora
 * @property int|null $recordar_minutos_antes
 * @property CarbonImmutable $notificar_en
 * @property CarbonImmutable|null $notificado_at
 * @property EstadoRecordatorio $estado
 * @property string|null $google_calendar_event_id
 * @property bool $google_calendar_incluir_creador
 */
#[Fillable([
    'causa_id', 'user_id', 'created_by', 'titulo', 'descripcion', 'fecha_hora',
    'recordar_minutos_antes', 'notificar_en', 'estado', 'notificado_at', 'google_calendar_event_id',
    'google_calendar_incluir_creador',
])]
class Recordatorio extends Model
{
    /** @use HasFactory<RecordatorioFactory> */
    use HasFactory;

    protected $attributes = [
        'estado' => EstadoRecordatorio::Pendiente->value,
    ];

    /** @return BelongsTo<Causa, $this> */
    public function causa(): BelongsTo
    {
        return $this->belongsTo(Causa::class);
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estaVencido(): bool
    {
        return $this->estado === EstadoRecordatorio::Pendiente && $this->fecha_hora->isPast();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'fecha_hora' => 'immutable_datetime',
            'notificar_en' => 'immutable_datetime',
            'notificado_at' => 'immutable_datetime',
            'estado' => EstadoRecordatorio::class,
            'google_calendar_incluir_creador' => 'boolean',
        ];
    }
}
