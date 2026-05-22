<?php

namespace App\Libraries;

use Config\Services;
use RuntimeException;

/**
 * GoogleSheetsCsv
 *
 * Lector ligero para CSVs publicados desde Google Sheets
 * (Archivo → Compartir → Publicar en la Web → CSV).
 *
 * No depende de la API de Google Sheets — usa el endpoint público.
 * Devuelve filas como arrays asociativos `[header => valor]`.
 */
class GoogleSheetsCsv
{
    public function __construct(
        private int $timeoutSeconds = 12
    ) {}

    /**
     * Descarga el CSV remoto y lo parsea a un array de filas asociativas.
     *
     * @return array<int, array<string, string>>
     *
     * @throws RuntimeException si la descarga o el parseo fallan
     */
    public function fetch(string $url): array
    {
        $body = $this->download($url);
        return $this->parse($body);
    }

    /**
     * Descarga el contenido como string. Aislado para poder mockear en tests.
     */
    private function download(string $url): string
    {
        // Usamos cURL via CodeIgniter Services para respetar la timeout
        // y los headers estandar, sin pedirle al usuario instalar libs extra.
        $client = Services::curlrequest([
            'timeout'         => $this->timeoutSeconds,
            'connect_timeout' => 6,
            'http_errors'     => false,
            'allow_redirects' => [
                'max'             => 5,
                'strict'          => false,
                'referer'         => true,
                'protocols'       => ['http', 'https'],
                'track_redirects' => false,
            ],
            'headers'         => [
                'Accept'     => 'text/csv,application/csv,text/plain;q=0.9,*/*;q=0.5',
                'User-Agent' => 'PrevCS-CSV-Importer/1.0',
            ],
        ]);

        $response = $client->get($url);
        $status   = $response->getStatusCode();

        if ($status < 200 || $status >= 300) {
            // Fallback con file_get_contents (sigue redirects via opciones HTTP)
            $body = $this->fallbackDownload($url);
            if ($body === null) {
                throw new RuntimeException(
                    "GoogleSheetsCsv: descarga fallida con HTTP {$status} para URL: {$url}"
                );
            }
            return $this->stripBom($body);
        }

        $body = (string) $response->getBody();
        if ($body === '') {
            throw new RuntimeException('GoogleSheetsCsv: respuesta vacía.');
        }

        return $this->stripBom($body);
    }

    private function stripBom(string $body): string
    {
        // El CSV publicado por Google Sheets viene en UTF-8. Removemos BOM si existe.
        if (str_starts_with($body, "\xEF\xBB\xBF")) {
            $body = substr($body, 3);
        }
        return $body;
    }

    /**
     * Fallback con stream HTTP nativo de PHP. Sigue redirects automáticamente.
     */
    private function fallbackDownload(string $url): ?string
    {
        $ctx = stream_context_create([
            'http' => [
                'method'        => 'GET',
                'follow_location' => 1,
                'max_redirects' => 5,
                'timeout'       => $this->timeoutSeconds,
                'header'        => "User-Agent: PrevCS-CSV-Importer/1.0\r\nAccept: text/csv,*/*;q=0.5\r\n",
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $ctx);
        return $body === false ? null : $body;
    }

    /**
     * Parsea el cuerpo CSV usando str_getcsv línea por línea para soportar
     * comas en valores entrecomillados.
     *
     * @return array<int, array<string, string>>
     */
    private function parse(string $body): array
    {
        // Normalizar saltos de línea (Google a veces incluye \r\n).
        $body = preg_replace("/\r\n|\r/", "\n", $body);

        $lines = array_values(array_filter(
            preg_split('/\n/', $body) ?: [],
            static fn($l) => trim($l) !== ''
        ));

        if (count($lines) < 1) {
            return [];
        }

        $headers = str_getcsv(array_shift($lines));
        $headers = array_map(static fn($h) => trim((string) $h), $headers);

        $rows = [];
        foreach ($lines as $line) {
            $parts = str_getcsv($line);
            // Saltar líneas vacías o con menos columnas que esperadas
            if (count(array_filter($parts, static fn($p) => $p !== '' && $p !== null)) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $i => $key) {
                $row[$key] = isset($parts[$i]) ? trim((string) $parts[$i]) : '';
            }
            $rows[] = $row;
        }

        return $rows;
    }
}
