<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * FormAnswerModel
 *
 * Modelo que maneja la tabla `form_answers` — respuestas importadas
 * desde Google Forms (vía la hoja publicada como CSV).
 *
 * - Cada fila representa una respuesta del formulario.
 * - `raw_timestamp` es UNIQUE para garantizar idempotencia en imports.
 * - `getMetrics()` devuelve los conteos por pregunta listos para Chart.js.
 */
class FormAnswerModel extends Model
{
    protected $table            = 'form_answers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'raw_timestamp',
        'submitted_at',
        'nombre',
        'rol_empresa',
        'conoce_similar',
        'funcion_util',
        'nivel_mejora',
        'es_ecologico',
    ];

    // No usamos los timestamps automáticos del Model porque `imported_at`
    // se setea via DEFAULT CURRENT_TIMESTAMP en MySQL.
    protected $useTimestamps = false;

    // ------------------------------------------------------------------
    // IMPORT helpers
    // ------------------------------------------------------------------

    /**
     * Devuelve los raw_timestamps que ya existen en la tabla, indexados
     * para lookup rápido al importar. Evita N consultas por fila.
     *
     * @return array<string, bool>
     */
    public function getExistingTimestampsMap(): array
    {
        $rows = $this->select('raw_timestamp')->findAll();
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['raw_timestamp']] = true;
        }
        return $map;
    }

    // ------------------------------------------------------------------
    // METRICS
    // ------------------------------------------------------------------

    /**
     * Devuelve un payload listo para los gráficos del portfolio:
     * total de respuestas + conteos por pregunta.
     *
     * @return array<string, mixed>
     */
    public function getMetrics(): array
    {
        $total = $this->countAllResults();

        return [
            'total'       => $total,
            'lastUpdate'  => date(\DateTimeInterface::ATOM),
            'questions'   => [
                'rol_empresa'    => [
                    'label'   => 'Rol en la empresa',
                    'order'   => ['Administración', 'Operador', 'Gerente'],
                    'counts'  => $this->countBy('rol_empresa'),
                ],
                'conoce_similar' => [
                    'label'   => 'Conoce u operó algún producto similar?',
                    'order'   => ['Sí', 'No'],
                    'counts'  => $this->countBy('conoce_similar'),
                ],
                'funcion_util'   => [
                    'label'   => 'Cual de las funciones del producto considera más útil para la empresa?',
                    'order'   => ['Stock', 'Gestión Mantenimiento', 'Gráficos', 'Sensor que monitoree un motor y presente datos en tiempo real'],
                    'counts'  => $this->countBy('funcion_util'),
                ],
                'nivel_mejora'   => [
                    'label'   => 'Qué nivel de mejora cree que se incrementará al adquirir esta tecnología en la empresa?',
                    'order'   => ['Bajo', 'Medio', 'Alto'],
                    'counts'  => $this->countBy('nivel_mejora'),
                ],
                'es_ecologico'   => [
                    'label'   => 'Le interesa que el desarrollo y funcionamiento del producto sea ecológico?',
                    'order'   => ['Si', 'No', 'Me es indiferente'],
                    'counts'  => $this->countBy('es_ecologico'),
                ],
            ],
        ];
    }

    /**
     * Cuenta las respuestas agrupadas por una columna.
     *
     * @return array<string, int>  ['Administración' => 4, 'Operador' => 5, ...]
     */
    private function countBy(string $column): array
    {
        // Whitelist defensivo
        $allowed = ['rol_empresa', 'conoce_similar', 'funcion_util', 'nivel_mejora', 'es_ecologico'];
        if (! in_array($column, $allowed, true)) {
            return [];
        }

        $rows = $this->select("$column AS value, COUNT(*) AS total")
                     ->where("$column IS NOT NULL", null, false)
                     ->groupBy($column)
                     ->findAll();

        $out = [];
        foreach ($rows as $r) {
            $key = trim((string) $r['value']);
            if ($key === '') {
                continue;
            }
            $out[$key] = (int) $r['total'];
        }
        return $out;
    }
}
