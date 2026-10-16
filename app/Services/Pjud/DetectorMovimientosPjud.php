<?php

namespace App\Services\Pjud;

use App\Models\Causa;
use App\Models\PjudMovimiento;
use App\Models\TribunalPjud;
use App\Support\Pjud\NormalizadorPjud;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Cruza el Estado Diario de un tribunal con las causas de SLAD.
 *
 * Una fila del listado corresponde a una causa cuando calzan los DOS datos
 * clave:
 *   1. Número de ingreso (Rol/RIT) = causas.numero_causa (normalizados).
 *      Si en SLAD la causa no tiene letra ("1234-2025"), calza por número-año.
 *   2. Nombre del tribunal = tribunal PJUD vinculado al juzgado de la causa.
 *      Si la fila no trae columna de tribunal, se usa el tribunal consultado.
 */
class DetectorMovimientosPjud
{
    /**
     * @param  list<EntradaEstadoDiario>  $entradas
     * @return list<array{0: PjudMovimiento, 1: EntradaEstadoDiario}> Sólo los movimientos NUEVOS, con su fila del listado.
     */
    public function registrar(TribunalPjud $tribunal, CarbonImmutable $fecha, array $entradas): array
    {
        if ($entradas === []) {
            return [];
        }

        $equivalencias = (array) config('pjud.equivalencias_nombre', []);
        $causasPorNumero = $this->causasDelTribunal($tribunal);

        if ($causasPorNumero->isEmpty()) {
            return [];
        }

        $nuevos = [];

        foreach ($entradas as $entrada) {
            $numero = NormalizadorPjud::numeroIngreso($entrada->numeroIngreso);

            // Respaldo: causas registradas en SLAD sin letra ("1234-2025").
            $clave = $numero !== null && $causasPorNumero->has($numero)
                ? $numero
                : NormalizadorPjud::numeroSinLetra($numero);

            if ($clave === null || ! $causasPorNumero->has($clave)) {
                continue;
            }

            /** @var Causa $causa */
            foreach ($causasPorNumero->get($clave) as $causa) {
                if (! $this->tribunalCoincide($entrada, $tribunal, $causa, $equivalencias)) {
                    continue;
                }

                $huella = hash('sha256', implode('|', [
                    $causa->id,
                    $tribunal->codigo,
                    $fecha->toDateString(),
                    $numero,
                    NormalizadorPjud::nombreTribunal($entrada->tramite),
                ]));

                $movimiento = PjudMovimiento::query()->createOrFirst(['huella' => $huella], [
                    'causa_id' => $causa->id,
                    'tribunal_pjud_codigo' => $tribunal->codigo,
                    'fecha_estado_diario' => $fecha->toDateString(),
                    'numero_ingreso' => mb_substr($entrada->numeroIngreso, 0, 100),
                    'tribunal_nombre' => mb_substr($entrada->tribunal ?? $tribunal->nombre, 0, 180),
                    'caratulado' => $entrada->caratulado === null ? null : mb_substr($entrada->caratulado, 0, 255),
                    'tramite' => $entrada->tramite,
                ]);

                if ($movimiento->wasRecentlyCreated) {
                    $nuevos[] = [$movimiento, $entrada];
                }
            }
        }

        return $nuevos;
    }

    /**
     * @return Collection<array-key, Collection<int, Causa>> número normalizado → causas
     */
    private function causasDelTribunal(TribunalPjud $tribunal): Collection
    {
        return Causa::query()
            ->select(['id', 'nombre', 'numero_causa', 'juzgado_id', 'responsable_id'])
            ->with('juzgado:id,nombre,tribunal_pjud_codigo')
            ->whereHas('juzgado', fn ($query) => $query->where('tribunal_pjud_codigo', $tribunal->codigo))
            ->whereNotNull('numero_causa')
            ->where('numero_causa', '!=', '')
            ->get()
            ->toBase()
            ->groupBy(fn (Causa $causa) => (string) NormalizadorPjud::numeroIngreso($causa->numero_causa))
            ->forget('');
    }

    /**
     * @param  array<string, string>  $equivalencias
     */
    private function tribunalCoincide(EntradaEstadoDiario $entrada, TribunalPjud $tribunal, Causa $causa, array $equivalencias): bool
    {
        if (blank($entrada->tribunal)) {
            return true; // El listado es del tribunal consultado.
        }

        $nombreFila = NormalizadorPjud::nombreTribunal($entrada->tribunal, $equivalencias);

        return in_array($nombreFila, [
            $tribunal->nombre_normalizado,
            NormalizadorPjud::nombreTribunal($causa->juzgado?->nombre, $equivalencias),
        ], true);
    }
}
