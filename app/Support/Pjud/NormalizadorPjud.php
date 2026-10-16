<?php

namespace App\Support\Pjud;

/**
 * Normaliza los dos datos clave del match con el Estado Diario:
 * el número de ingreso (Rol/RIT) y el nombre del tribunal.
 *
 * Es PHP puro (sin dependencias de Laravel) para poder probarlo aislado.
 */
final class NormalizadorPjud
{
    private const ACENTOS = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        'á' => 'A', 'é' => 'E', 'í' => 'I', 'ó' => 'O', 'ú' => 'U', 'ü' => 'U', 'ñ' => 'N',
        'º' => '', '°' => '', 'ª' => '', '–' => '-', '—' => '-',
    ];

    /**
     * "C-01234-2025", "c 1234 - 2025" o "Rol C-1234-2025" → "C-1234-2025".
     * "1234-2025" (sin letra, ej. Cortes) → "1234-2025".
     */
    public static function numeroIngreso(?string $valor): ?string
    {
        $texto = self::base($valor);

        if ($texto === '') {
            return null;
        }

        $texto = (string) preg_replace('/[()\[\]\.,:]/', ' ', $texto); // "(AMP) 11-2026" → "AMP 11-2026"
        $texto = (string) preg_replace('/\b(ROL|RIT|RUC|NRO|NUMERO|N)\b/', ' ', $texto);

        if (preg_match('/([A-Z]{1,15})\s*-?\s*0*(\d+)\s*-\s*(\d{4})/', $texto, $m) === 1) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }

        if (preg_match('/0*(\d+)\s*-\s*(\d{4})/', $texto, $m) === 1) {
            return "{$m[1]}-{$m[2]}";
        }

        $compacto = (string) preg_replace('/[^A-Z0-9]/', '', $texto);

        return $compacto === '' ? null : $compacto;
    }

    /**
     * Sólo "número-año" ("C-1234-2025" → "1234-2025"). Se usa como respaldo
     * cuando en SLAD la causa se registró sin la letra del tipo de causa.
     */
    public static function numeroSinLetra(?string $numeroNormalizado): ?string
    {
        if ($numeroNormalizado === null || preg_match('/(\d+)-(\d{4})$/', $numeroNormalizado, $m) !== 1) {
            return null;
        }

        return "{$m[1]}-{$m[2]}";
    }

    /**
     * "1º Juzgado de Letras en lo Civil de Antofagasta" → "1 JUZGADO CIVIL DE ANTOFAGASTA".
     *
     * @param  array<string, string>  $equivalencias  patrón regex → reemplazo
     */
    public static function nombreTribunal(?string $valor, array $equivalencias = []): string
    {
        $texto = self::base($valor);
        $texto = (string) preg_replace('/[^A-Z0-9 ]/', ' ', $texto);
        $texto = trim((string) preg_replace('/\s+/', ' ', $texto));

        foreach ($equivalencias as $patron => $reemplazo) {
            $texto = (string) preg_replace($patron, $reemplazo, $texto);
        }

        return trim((string) preg_replace('/\s+/', ' ', $texto));
    }

    /**
     * Variante sin ordinal inicial, para tribunales únicos que SLAD registra
     * como "1º Juzgado..." y el PJUD sin número (o al revés).
     */
    public static function sinOrdinalInicial(string $nombreNormalizado): string
    {
        return (string) preg_replace('/^\d+ /', '', $nombreNormalizado);
    }

    private static function base(?string $valor): string
    {
        $texto = strtr(trim((string) $valor), self::ACENTOS);

        return trim(mb_strtoupper($texto, 'UTF-8'));
    }
}
