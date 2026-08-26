<?php
function simboloMoeda($moeda) {
    $simbolos = [
        'BRL' => 'R$',
        'USD' => 'US$',
        'EUR' => '€'
    ];
    return $simbolos[$moeda] ?? 'R$';
}

function nomeMoeda($moeda) {
    $nomes = [
        'BRL' => 'Real (BRL)',
        'USD' => 'Dólar (USD)',
        'EUR' => 'Euro (EUR)'
    ];
    return $nomes[$moeda] ?? 'Real (BRL)';
}

function formatarMoeda($valor, $moeda = 'BRL') {
    return simboloMoeda($moeda) . ' ' . number_format((float)$valor, 2, ',', '.');
}

function moedaPermitida($moeda) {
    return in_array($moeda, ['BRL', 'USD', 'EUR'], true) ? $moeda : 'BRL';
}
?>
