<?php

use App\Support\Pjud\NormalizadorPjud;

test('normaliza el número de ingreso para el match con el estado diario', function (string $entrada, string $esperado) {
    expect(NormalizadorPjud::numeroIngreso($entrada))->toBe($esperado);
})->with([
    ['C-1234-2025', 'C-1234-2025'],
    ['c-01234-2025', 'C-1234-2025'],
    ['C 1234 - 2025', 'C-1234-2025'],
    ['Rol C-1234-2025', 'C-1234-2025'],
    ['RIT O-55-2026', 'O-55-2026'],
    ['Protección-1500-2026', 'PROTECCION-1500-2026'],
    ['1500-2026', '1500-2026'],
    ['(AMP) 11-2026', 'AMP-11-2026'],
    ['Ordinaria-5329-2025', 'ORDINARIA-5329-2025'],
]);

test('número de ingreso vacío no genera clave', function () {
    expect(NormalizadorPjud::numeroIngreso('  '))->toBeNull()
        ->and(NormalizadorPjud::numeroIngreso(null))->toBeNull();
});

test('normaliza nombres de tribunal entre SLAD y el PJUD', function () {
    $equivalencias = ['/\bJUZGADO DE LETRAS EN LO CIVIL\b/' => 'JUZGADO CIVIL', '/\bCOYHAIQUE\b/' => 'COIHAIQUE'];

    expect(NormalizadorPjud::nombreTribunal('1º Juzgado de Letras en lo Civil de Antofagasta', $equivalencias))
        ->toBe(NormalizadorPjud::nombreTribunal('1° JUZGADO CIVIL DE ANTOFAGASTA', $equivalencias))
        ->and(NormalizadorPjud::nombreTribunal('Juzgado de Garantía de Coyhaique', $equivalencias))->toBe('JUZGADO DE GARANTIA DE COIHAIQUE')
        ->and(NormalizadorPjud::sinOrdinalInicial('1 JUZGADO DE LETRAS DE PEUMO'))->toBe('JUZGADO DE LETRAS DE PEUMO');
});
