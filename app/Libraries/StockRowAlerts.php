<?php

namespace App\Libraries;

use App\Models\StockColumnModel;

/**
 * Evalúa el estado de alerta de una fila de stock.
 *
 * Es la única fuente de verdad de "esta fila está en problemas": el JS de la
 * vista pinta lo que dice esta clase, y el servidor la usa para decidir a quién
 * notificar. Antes la regla vivía duplicada en dos funciones de JavaScript y en
 * ningún lado de PHP, así que nadie que no estuviera mirando la pantalla se
 * enteraba de una baja de stock.
 *
 * Los tres niveles, de más a menos urgente:
 *   1  el contador llegó a 0
 *   2  el contador quedó en o por debajo del mínimo tolerable
 *   3  el contador cruzó el umbral propio de la fila (columna "Notificarme")
 *
 * Gana el más urgente: bajar de 26 a 0 de un saque avisa una sola vez, no tres.
 */
class StockRowAlerts
{
    public const LEVEL_NONE   = 0;
    public const LEVEL_ZERO   = 1;
    public const LEVEL_MINIMO = 2;
    public const LEVEL_AVISO  = 3;

    /**
     * @param array $columns   Columnas tal como las devuelve StockColumnModel::forTable()
     * @param array $values    Valores de la fila indexados por id de columna
     * @param array $units     Unidades propias de la fila indexadas por id de columna
     *
     * @return array{level:int,state:string,counter:float|null,minimo:float|null,threshold:float|null,unit:string,settings:array}
     */
    public static function evaluate(array $columns, array $values, array $units = []): array
    {
        $settings = self::settings($columns);

        $contadores = [];
        $minimos    = [];
        $umbral     = null;
        $unidad     = 'unidades';

        foreach ($columns as $column) {
            $id    = (int) $column['id'];
            $valor = $values[$id] ?? null;

            switch ($column['type']) {
                case StockColumnModel::TYPE_CONTADOR:
                    $contadores[] = (float) ($valor ?? 0);
                    $unidad       = trim((string) ($units[$id] ?? $column['unit'] ?? '')) ?: 'unidades';
                    break;

                case StockColumnModel::TYPE_MINIMO:
                    $minimos[] = (float) ($valor ?? 0);
                    break;

                case StockColumnModel::TYPE_AVISO:
                    // Celda vacía: esta fila no tiene aviso personalizado.
                    $umbral = ($valor === null || $valor === '') ? null : (float) $valor;
                    break;
            }
        }

        if ($contadores === []) {
            return self::result(self::LEVEL_NONE, 'ok', null, null, $umbral, $unidad, $settings);
        }

        // Con más de un contador manda el más bajo, y con más de un mínimo el
        // más alto: así ningún nivel de referencia queda sin respetar.
        $contador = min($contadores);
        $minimo   = $minimos === [] ? null : max($minimos);

        // El estado visual no depende de los interruptores: la celda se pinta
        // igual aunque el usuario haya apagado ese aviso.
        $bajoMinimo = $minimo !== null && $contador <= $minimo;
        $enUmbral   = $umbral !== null && self::compare($contador, $umbral, $settings['comparator']);

        if ($contador <= 0.0) {
            $state = 'zero';
        } elseif ($bajoMinimo) {
            $state = $contador == $minimo ? 'equal' : 'low';
        } elseif ($enUmbral) {
            $state = 'notice';
        } else {
            $state = 'ok';
        }

        // Cascada: apagar un nivel no silencia a los de abajo. Un contador en 0
        // con el aviso de cero apagado sigue estando bajo el mínimo.
        if ($contador <= 0.0 && $settings['zero']) {
            $level = self::LEVEL_ZERO;
        } elseif ($bajoMinimo && $settings['minimo']) {
            $level = self::LEVEL_MINIMO;
        } elseif ($enUmbral) {
            $level = self::LEVEL_AVISO;
        } else {
            $level = self::LEVEL_NONE;
        }

        return self::result($level, $state, $contador, $minimo, $umbral, $unidad, $settings);
    }

    /**
     * Texto de la notificación para un estado ya evaluado. Devuelve null cuando
     * no hay nada que avisar.
     *
     * @return array{level:int,title:string,text:string}|null
     */
    public static function message(array $alert, string $item, string $tableTitle): ?array
    {
        if ($alert['level'] === self::LEVEL_NONE) {
            return null;
        }

        $item  = $item !== '' ? $item : 'Ítem sin nombre';
        $tabla = $tableTitle !== '' ? $tableTitle : 'Tabla de stock';

        switch ($alert['level']) {
            case self::LEVEL_ZERO:
                return [
                    'level' => self::LEVEL_ZERO,
                    'title' => 'Stock agotado',
                    'text'  => $item . ' llegó a 0 en ' . $tabla . '.',
                ];

            case self::LEVEL_MINIMO:
                return [
                    'level' => self::LEVEL_MINIMO,
                    'title' => 'Stock en el mínimo tolerable',
                    'text'  => $item . ' quedó en ' . self::num($alert['counter']) . ' ' . $alert['unit']
                        . ', en o por debajo del mínimo tolerable (' . self::num($alert['minimo']) . ') en ' . $tabla . '.',
                ];

            default:
                $etiqueta = trim((string) $alert['settings']['label']) ?: 'Aviso de stock';

                return [
                    'level' => self::LEVEL_AVISO,
                    'title' => $etiqueta,
                    'text'  => $item . ' quedó en ' . self::num($alert['counter']) . ' ' . $alert['unit']
                        . ', ' . self::comparatorText($alert['settings']['comparator'])
                        . ' ' . self::num($alert['threshold']) . ' en ' . $tabla . '.',
                ];
        }
    }

    /**
     * Config de notificaciones de la tabla. Sin columna "Notificarme" valen los
     * defaults, o sea el comportamiento de siempre: los avisos automáticos de
     * cero y de mínimo van prendidos.
     */
    public static function settings(array $columns): array
    {
        foreach ($columns as $column) {
            if ($column['type'] === StockColumnModel::TYPE_AVISO) {
                return StockColumnModel::settingsFor($column);
            }
        }

        return StockColumnModel::AVISO_DEFAULTS;
    }

    private static function compare(float $valor, float $umbral, string $comparator): bool
    {
        switch ($comparator) {
            case 'lt':
                return $valor < $umbral;
            case 'eq':
                return $valor == $umbral;
            default:
                return $valor <= $umbral;
        }
    }

    private static function comparatorText(string $comparator): string
    {
        switch ($comparator) {
            case 'lt':
                return 'por debajo de';
            case 'eq':
                return 'justo en';
            default:
                return 'en o por debajo de';
        }
    }

    /** 12.0 → "12"; 12.5 → "12.5". */
    private static function num(?float $value): string
    {
        if ($value === null) {
            return '0';
        }

        return $value == (int) $value ? (string) (int) $value : rtrim(rtrim(number_format($value, 3, '.', ''), '0'), '.');
    }

    private static function result(int $level, string $state, ?float $counter, ?float $minimo, ?float $threshold, string $unit, array $settings): array
    {
        return [
            'level'     => $level,
            'state'     => $state,
            'counter'   => $counter,
            'minimo'    => $minimo,
            'threshold' => $threshold,
            'unit'      => $unit,
            'settings'  => $settings,
        ];
    }
}
