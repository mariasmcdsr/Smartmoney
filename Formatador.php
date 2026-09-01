<?php
class Formatador {
    public static function simboloMoeda($moeda) {
        $simbolos = ['BRL' => 'R$', 'USD' => 'US$', 'EUR' => '€'];
        return $simbolos[$moeda] ?? 'R$';
    }

    public static function nomeMoeda($moeda) {
        $nomes = ['BRL' => 'Real (BRL)', 'USD' => 'Dólar (USD)', 'EUR' => 'Euro (EUR)'];
        return $nomes[$moeda] ?? 'Real (BRL)';
    }

    public static function formatarMoeda($valor, $moeda = 'BRL') {
        return self::simboloMoeda($moeda) . ' ' . number_format((float)$valor, 2, ',', '.');
    }

    public static function moedaPermitida($moeda) {
        return in_array($moeda, ['BRL', 'USD', 'EUR'], true) ? $moeda : 'BRL';
    }
}
?>