<?php

namespace App\Controllers;

use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SpaceEventModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * API JSON del calendario por espacio (pestaña "Calendario" en
 * espacios/:id/stock). La vista la sirve StockController::index.
 *
 * Mismas reglas de scope que el stock: el id del espacio va en la URL, hay que
 * ser miembro para verlo y tener permiso de edición para escribir. Un evento
 * de otro espacio responde 404.
 */
class SpaceEventController extends BaseController
{
    private const DATE_FORMAT = 'Y-m-d\TH:i';

    public function create($industryId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $data = $this->validated($this->payload());
        if (is_string($data)) {
            return $this->fail($data);
        }

        $model   = new SpaceEventModel();
        $eventId = (int) $model->insert($data + [
            'industry_id' => (int) $industryId,
            'created_by'  => (int) session('user_id') ?: null,
        ], true);

        if ($eventId <= 0) {
            return $this->fail('No se pudo crear el evento.', 500);
        }

        return $this->ok(['event' => $model->payload($eventId)]);
    }

    public function update($industryId = null, $eventId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $model = new SpaceEventModel();
        $event = $model->findInIndustry((int) $eventId, (int) $industryId);
        if ($event === null) {
            return $this->fail('Ese evento no existe en este espacio.', 404);
        }

        $data = $this->validated($this->payload());
        if (is_string($data)) {
            return $this->fail($data);
        }

        $model->update((int) $event['id'], $data);

        return $this->ok(['event' => $model->payload((int) $event['id'])]);
    }

    public function delete($industryId = null, $eventId = null)
    {
        if (($error = $this->resolve((int) $industryId)) !== null) {
            return $error;
        }

        $model = new SpaceEventModel();
        $event = $model->findInIndustry((int) $eventId, (int) $industryId);
        if ($event === null) {
            return $this->fail('Ese evento no existe en este espacio.', 404);
        }

        $model->delete((int) $event['id']);

        return $this->ok(['id' => (string) $event['id']]);
    }

    /**
     * Normaliza el evento completo que manda el cliente. Devuelve las columnas
     * listas para guardar o el mensaje de error. Color, categoría y etiquetas
     * se validan contra las listas del modelo: el cliente es manipulable.
     */
    private function validated(array $raw)
    {
        $title = $this->text($raw['title'] ?? '', 200);
        if ($title === '') {
            return 'El evento necesita un título.';
        }

        $start = $this->date($raw['startTime'] ?? null);
        $end   = $this->date($raw['endTime'] ?? null);
        if ($start === null || $end === null) {
            return 'Elegí la fecha y hora de inicio y de fin.';
        }
        if ($end <= $start) {
            return 'El fin tiene que ser posterior al inicio.';
        }

        $color = (string) ($raw['color'] ?? '');
        if (! in_array($color, SpaceEventModel::COLORS, true)) {
            $color = SpaceEventModel::COLORS[0];
        }

        $category = (string) ($raw['category'] ?? '');
        if (! in_array($category, SpaceEventModel::CATEGORIES, true)) {
            $category = null;
        }

        $tags = is_array($raw['tags'] ?? null) ? $raw['tags'] : [];
        $tags = array_values(array_intersect(SpaceEventModel::TAGS, $tags));

        return [
            'title'       => $title,
            'description' => $this->text($raw['description'] ?? '', 2000) ?: null,
            'start_at'    => $start->format('Y-m-d H:i:s'),
            'end_at'      => $end->format('Y-m-d H:i:s'),
            'color'       => $color,
            'category'    => $category,
            'tags'        => json_encode($tags, JSON_UNESCAPED_UNICODE),
        ];
    }

    private function date($value): ?\DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        $date   = \DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value);
        $errors = \DateTimeImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        return $date;
    }

    /** Membresía + permiso de escritura. null si todo está bien. */
    private function resolve(int $industryId): ?ResponseInterface
    {
        $userId      = (int) session('user_id');
        $memberships = new IndustryUserModel();

        if ($industryId <= 0 || ! $memberships->userBelongsTo($userId, $industryId)) {
            return $this->fail('No tenés acceso al calendario de ese espacio.', 404);
        }

        if (! $memberships->canEdit($userId, $industryId)) {
            return $this->fail('No tenés permiso para editar el calendario de este espacio.', 403);
        }

        if ((new IndustryModel())->find($industryId) === null) {
            return $this->fail('Ese espacio ya no existe.', 404);
        }

        return null;
    }

    /** Cuerpo de la petición, venga como JSON o como POST clásico. */
    private function payload(): array
    {
        $json = $this->request->getJSON(true);
        if (is_array($json)) {
            return $json;
        }

        return $this->request->getPost() ?? [];
    }

    /** Recorta, limita y saca caracteres de control (los saltos de línea quedan). */
    private function text($value, int $max): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    /** Respuesta correcta: siempre viaja el CSRF nuevo para el próximo POST. */
    private function ok(array $payload): ResponseInterface
    {
        return $this->response->setJSON(['ok' => true, 'csrf' => csrf_hash()] + $payload);
    }

    private function fail(string $message, int $status = 422): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(['ok' => false, 'error' => $message, 'csrf' => csrf_hash()]);
    }
}
