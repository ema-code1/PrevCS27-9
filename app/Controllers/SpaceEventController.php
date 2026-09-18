<?php

namespace App\Controllers;

use App\Libraries\SpaceEventReminders;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SpaceEventAssigneeModel;
use App\Models\SpaceEventModel;
use App\Models\SpaceEventTagModel;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * API JSON del calendario por espacio (pestaña "Calendario" en
 * espacios/:id/stock). La vista la sirve StockController::index.
 *
 * Mismas reglas de scope que el stock: el id del espacio va en la URL, hay que
 * ser miembro para verlo y tener permiso de edición para escribir. Un evento
 * de otro espacio responde 404.
 *
 * Todo lo que llega del cliente se valida contra listas del servidor: color y
 * categoría contra las del modelo, encargados contra los miembros del espacio
 * y etiquetas contra las fijas más las propias del espacio.
 */
class SpaceEventController extends BaseController
{
    private const DATE_FORMAT = 'Y-m-d\TH:i';

    public function create($industryId = null)
    {
        $industryId = (int) $industryId;
        if (($error = $this->resolve($industryId)) !== null) {
            return $error;
        }

        $data = $this->validated($this->payload(), $industryId);
        if (is_string($data)) {
            return $this->fail($data);
        }

        $assignees = $data['assignees'];
        unset($data['assignees']);

        $model   = new SpaceEventModel();
        $eventId = (int) $model->insert($data + [
            'industry_id' => $industryId,
            'created_by'  => (int) session('user_id') ?: null,
        ], true);

        if ($eventId <= 0) {
            return $this->fail('No se pudo crear el evento.', 500);
        }

        (new SpaceEventAssigneeModel())->sync($eventId, $assignees);

        return $this->ok(['event' => $model->payload($eventId)]);
    }

    public function update($industryId = null, $eventId = null)
    {
        $industryId = (int) $industryId;
        if (($error = $this->resolve($industryId)) !== null) {
            return $error;
        }

        $model = new SpaceEventModel();
        $event = $model->findInIndustry((int) $eventId, $industryId);
        if ($event === null) {
            return $this->fail('Ese evento no existe en este espacio.', 404);
        }

        $data = $this->validated($this->payload(), $industryId, $event);
        if (is_string($data)) {
            return $this->fail($data);
        }

        $assignees = $data['assignees'];
        unset($data['assignees']);

        $model->update((int) $event['id'], $data);
        (new SpaceEventAssigneeModel())->sync((int) $event['id'], $assignees);

        return $this->ok(['event' => $model->payload((int) $event['id'])]);
    }

    public function delete($industryId = null, $eventId = null)
    {
        $industryId = (int) $industryId;
        if (($error = $this->resolve($industryId)) !== null) {
            return $error;
        }

        $model = new SpaceEventModel();
        $event = $model->findInIndustry((int) $eventId, $industryId);
        if ($event === null) {
            return $this->fail('Ese evento no existe en este espacio.', 404);
        }

        // space_event_assignees cuelga del evento con ON DELETE CASCADE.
        $model->delete((int) $event['id']);

        return $this->ok(['id' => (string) $event['id']]);
    }

    /* =================================================================
       Etiquetas propias del espacio
       ================================================================= */

    /**
     * Crea una etiqueta del espacio. Las fijas (SpaceEventModel::TAGS) ya
     * están disponibles en todos los espacios, así que repetir una de esas es
     * un error y no un duplicado silencioso.
     */
    public function createTag($industryId = null)
    {
        $industryId = (int) $industryId;
        if (($error = $this->resolve($industryId)) !== null) {
            return $error;
        }

        $name = $this->text($this->payload()['name'] ?? '', SpaceEventTagModel::MAX_LENGTH);
        if ($name === '') {
            return $this->fail('La etiqueta necesita un nombre.');
        }

        // Una sola línea: el nombre es el texto de una píldora.
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        if ($name === '') {
            return $this->fail('La etiqueta necesita un nombre.');
        }

        $tags = new SpaceEventTagModel();

        foreach (array_merge(SpaceEventModel::TAGS, $tags->namesForIndustry($industryId)) as $existing) {
            if (mb_strtolower($existing) === mb_strtolower($name)) {
                return $this->fail('Ya existe la etiqueta "' . $existing . '".');
            }
        }

        if ($tags->countForIndustry($industryId) >= SpaceEventTagModel::MAX_PER_INDUSTRY) {
            return $this->fail('Llegaste al máximo de ' . SpaceEventTagModel::MAX_PER_INDUSTRY . ' etiquetas propias.');
        }

        $tagId = (int) $tags->insert([
            'industry_id' => $industryId,
            'name'        => $name,
            'created_by'  => (int) session('user_id') ?: null,
        ], true);

        if ($tagId <= 0) {
            return $this->fail('No se pudo crear la etiqueta.', 500);
        }

        return $this->ok([
            'tag'  => ['id' => $tagId, 'name' => $name],
            'tags' => $tags->payloadForIndustry($industryId),
        ]);
    }

    /**
     * Borra una etiqueta propia y la saca de los eventos que la tenían: si
     * quedara pegada en el JSON se seguiría viendo sin poder volver a elegirla.
     */
    public function deleteTag($industryId = null, $tagId = null)
    {
        $industryId = (int) $industryId;
        if (($error = $this->resolve($industryId)) !== null) {
            return $error;
        }

        $tags = new SpaceEventTagModel();
        $tag  = $tags->findInIndustry((int) $tagId, $industryId);
        if ($tag === null) {
            return $this->fail('Esa etiqueta no existe en este espacio.', 404);
        }

        $tags->delete((int) $tag['id']);
        (new SpaceEventModel())->stripTag($industryId, (string) $tag['name']);

        return $this->ok([
            'id'     => (string) $tag['id'],
            'tags'   => $tags->payloadForIndustry($industryId),
            'events' => (new SpaceEventModel())->payloadForIndustry($industryId),
        ]);
    }

    /* =================================================================
       Normalización
       ================================================================= */

    /**
     * Normaliza el evento completo que manda el cliente. Devuelve las columnas
     * listas para guardar (más la lista de encargados, que va a otra tabla) o
     * el mensaje de error.
     *
     * $event es el evento que ya está guardado, cuando se está editando: sirve
     * para no volver a repartir un recordatorio que ya salió.
     */
    private function validated(array $raw, int $industryId, ?array $event = null)
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

        // Fijas + propias del espacio, en ese orden.
        $allowedTags = array_merge(
            SpaceEventModel::TAGS,
            (new SpaceEventTagModel())->namesForIndustry($industryId)
        );
        $tags = is_array($raw['tags'] ?? null) ? $raw['tags'] : [];
        $tags = array_values(array_intersect($allowedTags, $tags));

        $notify   = SpaceEventReminders::normalize($raw['notify'] ?? null);
        $reminder = $event === null
            ? SpaceEventReminders::columnsFor($start, $notify)
            : SpaceEventReminders::columnsForUpdate($event, $start, $notify);

        return [
            'title'       => $title,
            'description' => $this->text($raw['description'] ?? '', 2000) ?: null,
            'start_at'    => $start->format('Y-m-d H:i:s'),
            'end_at'      => $end->format('Y-m-d H:i:s'),
            'color'       => $color,
            'category'    => $category,
            'tags'        => json_encode($tags, JSON_UNESCAPED_UNICODE),
            'assignees'   => $this->assignees($raw['assignees'] ?? null, $industryId),
        ] + $reminder;
    }

    /**
     * Encargados válidos: solo usuarios que hoy tienen acceso al espacio. Un id
     * de afuera se descarta en silencio, igual que un color inventado.
     *
     * @return list<int>
     */
    private function assignees($raw, int $industryId): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $members = array_map(
            static fn (array $member): int => (int) $member['user_id'],
            (new IndustryUserModel())->membersForIndustry($industryId)
        );

        $wanted = array_map('intval', array_filter($raw, 'is_scalar'));

        return array_values(array_unique(array_intersect($wanted, $members)));
    }

    private function date($value): ?DateTimeImmutable
    {
        if (! is_string($value)) {
            return null;
        }

        $date   = DateTimeImmutable::createFromFormat('!' . self::DATE_FORMAT, $value);
        $errors = DateTimeImmutable::getLastErrors();

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
