<?php

namespace App\Libraries;

use App\Models\FormAnswerModel;
use DateTime;
use Throwable;

/**
 * FormImportService
 *
 * Orquesta el flujo:
 *   CSV (Google Sheets publicado) → normalización → INSERT en form_answers
 *
 * - Idempotente: las filas duplicadas (mismo raw_timestamp) se omiten.
 * - Devuelve un resumen (inserted / skipped / errors) para logging.
 */
class FormImportService
{
    /**
     * URL pública del CSV publicado por Google Sheets.
     * Se puede sobreescribir desde .env con la clave  formImport.csvUrl
     */
    public const DEFAULT_CSV_URL =
        'https://docs.google.com/spreadsheets/d/e/2PACX-1vSJzvq_T9GkWPfczoNLs-pIvblRkOfmW4PqozN9op8pwIeN1T5wKsfLscoKc0FVJktxp_mo95-z4i6Y/pub?output=csv';

    /**
     * Mapeo del header del CSV (tal cual viene de Google) → columna en MySQL.
     * Si la profesora renombra columnas en el formulario, hay que actualizar acá.
     */
    private const COLUMN_MAP = [
        'Marca temporal'                                                                                         => 'raw_timestamp',
        'Nombre'                                                                                                 => 'nombre',
        'Rol en la empresa'                                                                                      => 'rol_empresa',
        'Conoce u operó algún producto similar?'                                                                 => 'conoce_similar',
        'Cual de las funciones del producto considera más útil para la empresa?'                                 => 'funcion_util',
        'Qué nivel de mejora cree que se incrementará al adquirir esta tecnología en la empresa?'                => 'nivel_mejora',
        'Le interesa que el desarrollo y funcionamiento del producto sea ecológico?'                             => 'es_ecologico',
    ];

    public function __construct(
        private GoogleSheetsCsv $csv = new GoogleSheetsCsv(),
        private FormAnswerModel $model = new FormAnswerModel()
    ) {}

    /**
     * Ejecuta el import desde la URL.
     *
     * @return array{
     *   ok: bool,
     *   inserted: int,
     *   skipped: int,
     *   errors: array<int, string>,
     *   total_in_csv: int
     * }
     */
    public function importFromUrl(?string $url = null): array
    {
        $url ??= env('formImport.csvUrl', self::DEFAULT_CSV_URL);

        $errors   = [];
        $inserted = 0;
        $skipped  = 0;

        try {
            $rows = $this->csv->fetch($url);
        } catch (Throwable $e) {
            return [
                'ok'           => false,
                'inserted'     => 0,
                'skipped'      => 0,
                'errors'       => [$e->getMessage()],
                'total_in_csv' => 0,
            ];
        }

        if ($rows === []) {
            return [
                'ok'           => true,
                'inserted'     => 0,
                'skipped'      => 0,
                'errors'       => ['CSV vacío'],
                'total_in_csv' => 0,
            ];
        }

        // Pre-cargamos los timestamps existentes para evitar N queries.
        $existing = $this->model->getExistingTimestampsMap();

        foreach ($rows as $i => $row) {
            $normalized = $this->normalize($row);

            if ($normalized === null) {
                $errors[] = "Fila " . ($i + 2) . ": sin Marca temporal — saltada";
                continue;
            }

            $raw = $normalized['raw_timestamp'];
            if (isset($existing[$raw])) {
                $skipped++;
                continue;
            }

            try {
                $this->model->insert($normalized);
                $existing[$raw] = true; // por si la URL trae duplicados en la misma corrida
                $inserted++;
            } catch (Throwable $e) {
                $errors[] = "Fila " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        return [
            'ok'           => $errors === [],
            'inserted'     => $inserted,
            'skipped'      => $skipped,
            'errors'       => $errors,
            'total_in_csv' => count($rows),
        ];
    }

    /**
     * Normaliza una fila del CSV al esquema de la tabla.
     * Devuelve null si la fila no tiene "Marca temporal" (es inválida).
     *
     * @param array<string,string> $row
     * @return array<string,string>|null
     */
    private function normalize(array $row): ?array
    {
        $rawTs = trim($row['Marca temporal'] ?? '');
        if ($rawTs === '') {
            return null;
        }

        return [
            'raw_timestamp'  => $rawTs,
            'submitted_at'   => $this->parseDate($rawTs),
            'nombre'         => $this->cleanString($row['Nombre'] ?? null, 200),
            'rol_empresa'    => $this->cleanString($row['Rol en la empresa'] ?? null, 80),
            'conoce_similar' => $this->cleanString($row['Conoce u operó algún producto similar?'] ?? null, 20),
            'funcion_util'   => $this->cleanString($row['Cual de las funciones del producto considera más útil para la empresa?'] ?? null, 200),
            'nivel_mejora'   => $this->cleanString($row['Qué nivel de mejora cree que se incrementará al adquirir esta tecnología en la empresa?'] ?? null, 20),
            'es_ecologico'   => $this->cleanString($row['Le interesa que el desarrollo y funcionamiento del producto sea ecológico?'] ?? null, 40),
        ];
    }

    /**
     * Convierte "6/5/2026 12:57:59" (formato D/M/YYYY HH:MM:SS de Google Forms
     * configurado en español/AR) a "2026-05-06 12:57:59" (DATETIME MySQL).
     *
     * Si no se puede parsear, devuelve null para no romper el insert.
     */
    private function parseDate(string $raw): ?string
    {
        // Probamos varios formatos comunes de Google Forms.
        $formats = ['j/n/Y H:i:s', 'd/m/Y H:i:s', 'n/j/Y H:i:s', 'Y-m-d H:i:s'];

        foreach ($formats as $fmt) {
            $dt = DateTime::createFromFormat($fmt, $raw);
            if ($dt instanceof DateTime && $dt->format($fmt) === $raw) {
                return $dt->format('Y-m-d H:i:s');
            }
        }
        // Fallback: parseo permisivo
        try {
            $dt = new DateTime($raw);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    private function cleanString(?string $value, int $maxLen): ?string
    {
        if ($value === null) {
            return null;
        }
        $v = trim($value);
        if ($v === '') {
            return null;
        }
        return mb_substr($v, 0, $maxLen, 'UTF-8');
    }
}
