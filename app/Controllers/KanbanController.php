<?php

namespace App\Controllers;

use App\Models\Esp32DeviceModel;
use App\Models\DeviceAccessModel;
use App\Models\IndustryUserModel;
use App\Models\KanbanActivityModel;
use App\Models\KanbanAttachmentModel;
use App\Models\KanbanBoardModel;
use App\Models\KanbanChecklistItemModel;
use App\Models\KanbanChecklistModel;
use App\Models\KanbanColumnModel;
use App\Models\KanbanCommentModel;
use App\Models\KanbanLabelModel;
use App\Models\KanbanLinkModel;
use App\Models\KanbanTaskAssigneeModel;
use App\Models\KanbanTaskLabelModel;
use App\Models\KanbanTaskModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Database;

/**
 * API JSON del tablero Kanban de cada ESP32.
 *
 * Reglas de scope (se aplican en TODOS los endpoints):
 *   1. La URL siempre lleva el id de la placa.
 *   2. La placa se resuelve a su espacio (industry) y se verifica que el
 *      usuario logueado sea miembro de ese espacio.
 *   3. El tablero se busca por esp32_device_id, nunca por id suelto.
 *   4. Columnas, tareas, checklists, comentarios, adjuntos y enlaces se
 *      buscan siempre con findInBoard(), que exige que pertenezcan a ese
 *      tablero. Mandar el id de un elemento de otra placa devuelve 404.
 */
class KanbanController extends BaseController
{
    /** Tamaño máximo por adjunto (5 MB). */
    private const MAX_UPLOAD_BYTES = 5 * 1024 * 1024;

    /** Extensiones permitidas para adjuntos. */
    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg',
        'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx',
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
        /** Días que una tarea completada se conserva antes de borrarse sola. */
    private const COMPLETED_TTL_DAYS = 30;
    // para demostracion cambiar _DAYS = 0; para que la tarea se purgue al instante
    // p/ello recargar página F5 pq al completar una task con taskAction (JS) no recarga la pág

    private ?array $device = null;
    private ?array $board  = null;

    /* =================================================================
       Tablero
       ================================================================= */

        /** GET · estado completo del tablero. */
    public function board($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        // Aprovechamos la carga del tablero para limpiar las tareas
        // completadas que ya vencieron (más de COMPLETED_TTL_DAYS días).
        $this->purgeExpiredCompleted((int) $this->board['id']);

        return $this->ok($this->boardPayload());
    }

    /** POST · nombre / descripción / color de fondo del tablero. */
    public function updateBoard($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $data    = $this->payload();
        $changes = [];

        if (array_key_exists('name', $data)) {
            $name = $this->text($data['name'], 150);
            if ($name === '') {
                return $this->fail('El tablero necesita un nombre.');
            }
            $changes['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $description = $this->text($data['description'], 2000);
            $changes['description'] = $description !== '' ? $description : null;
        }

        if (array_key_exists('background_color', $data)) {
            if (! KanbanBoardModel::isValidColor((string) $data['background_color'])) {
                return $this->fail('Ese color no está en la paleta.');
            }
            $changes['background_color'] = (string) $data['background_color'];
        }

        if ($changes !== []) {
            (new KanbanBoardModel())->update((int) $this->board['id'], $changes);
            // El payload de respuesta se arma con $this->board, que se leyó
            // antes del update: sin esto la respuesta devuelve el nombre
            // viejo y el cliente pisa lo que el usuario acaba de escribir.
            $this->board = array_merge($this->board, $changes);
            $this->log(null, 'board_updated', 'actualizó el tablero');
        }

        return $this->ok($this->boardPayload());
    }

    /* =================================================================
       Columnas
       ================================================================= */

    public function createColumn($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $data = $this->payload();
        $name = $this->text($data['name'] ?? '', 120);
        if ($name === '') {
            return $this->fail('La columna necesita un nombre.');
        }

        $columnModel = new KanbanColumnModel();
        $color = (string) ($data['color'] ?? 'gris');

        $columnModel->insert([
            'board_id'   => (int) $this->board['id'],
            'name'       => $name,
            'color'      => KanbanBoardModel::isValidColor($color) ? $color : 'gris',
            'position'   => $columnModel->nextPosition((int) $this->board['id']),
            'status_key' => $this->text($data['status_key'] ?? '', 40) ?: null,
        ]);

        $this->log(null, 'column_created', 'creó la columna «' . $name . '»');

        return $this->ok($this->boardPayload());
    }

    public function updateColumn($deviceId = null, $columnId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $columnModel = new KanbanColumnModel();
        $column = $columnModel->findInBoard((int) $columnId, (int) $this->board['id']);
        if ($column === null) {
            return $this->fail('Esa columna no existe en este tablero.', 404);
        }

        $data    = $this->payload();
        $changes = [];

        if (array_key_exists('name', $data)) {
            $name = $this->text($data['name'], 120);
            // Un título vacío no se guarda: el cliente restaura el anterior.
            if ($name === '') {
                return $this->fail('El nombre de la columna no puede quedar vacío.');
            }
            $changes['name'] = $name;
        }

        if (array_key_exists('description', $data)) {
            $description = $this->text($data['description'], 255);
            $changes['description'] = $description !== '' ? $description : null;
        }

        if (array_key_exists('color', $data)) {
            if (! KanbanBoardModel::isValidColor((string) $data['color'])) {
                return $this->fail('Ese color no está en la paleta.');
            }
            $changes['color'] = (string) $data['color'];
        }

        if (array_key_exists('status_key', $data)) {
            $status = $this->text($data['status_key'], 40);
            $changes['status_key'] = $status !== '' ? $status : null;
        }

        if (array_key_exists('is_final', $data)) {
            $changes['is_final'] = $this->flag($data['is_final']);
        }

        if (array_key_exists('task_limit', $data)) {
            $limit = $data['task_limit'];
            if ($limit === '' || $limit === null) {
                $changes['task_limit'] = null;
            } else {
                $limit = (int) $limit;
                if ($limit < 1 || $limit > 999) {
                    return $this->fail('El límite tiene que estar entre 1 y 999.');
                }
                $changes['task_limit'] = $limit;
            }
        }

        if (array_key_exists('is_collapsed', $data)) {
            $changes['is_collapsed'] = $this->flag($data['is_collapsed']);
        }

        if ($changes !== []) {
            $columnModel->update((int) $column['id'], $changes);
            $this->log(null, 'column_updated', 'editó la columna «' . ($changes['name'] ?? $column['name']) . '»');
        }

        return $this->ok($this->boardPayload());
    }

    /**
     * Acciones de columna que no son una simple edición de campos:
     * archivar, restaurar, duplicar, vaciar y eliminar.
     */
    public function columnAction($deviceId = null, $columnId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $columnModel = new KanbanColumnModel();
        $taskModel   = new KanbanTaskModel();
        $boardId     = (int) $this->board['id'];

        $column = $columnModel->findInBoard((int) $columnId, $boardId);
        if ($column === null) {
            return $this->fail('Esa columna no existe en este tablero.', 404);
        }

        $action = (string) ($this->payload()['action'] ?? '');
        $db     = Database::connect();

        switch ($action) {
            case 'archive':
                $db->transStart();
                $columnModel->update((int) $column['id'], ['is_archived' => 1]);
                // Las tareas se archivan con la columna para que no queden
                // "vivas" colgando de un contenedor invisible.
                $db->table('kanban_tasks')
                    ->where('column_id', (int) $column['id'])
                    ->update(['is_archived' => 1]);
                $db->transComplete();
                $this->log(null, 'column_archived', 'archivó la columna «' . $column['name'] . '»');
                break;

            case 'restore':
                $db->transStart();
                $columnModel->update((int) $column['id'], [
                    'is_archived' => 0,
                    'position'    => $columnModel->nextPosition($boardId),
                ]);
                $db->table('kanban_tasks')
                    ->where('column_id', (int) $column['id'])
                    ->update(['is_archived' => 0]);
                $db->transComplete();
                $this->log(null, 'column_restored', 'restauró la columna «' . $column['name'] . '»');
                break;

            case 'duplicate':
                $db->transStart();
                $newId = $columnModel->insert([
                    'board_id'   => $boardId,
                    'name'       => $this->text($column['name'] . ' (copia)', 120),
                    'description' => $column['description'],
                    'color'      => $column['color'],
                    'position'   => $columnModel->nextPosition($boardId),
                    'status_key' => $column['status_key'],
                    'is_final'   => (int) $column['is_final'],
                    'task_limit' => $column['task_limit'],
                ], true);

                if ($newId !== false) {
                    foreach ($taskModel->where('column_id', (int) $column['id'])->where('is_archived', 0)
                        ->orderBy('position', 'ASC')->findAll() as $task) {
                        $this->cloneTask($task, (int) $newId, (int) $task['position']);
                    }
                }
                $db->transComplete();
                $this->log(null, 'column_duplicated', 'duplicó la columna «' . $column['name'] . '»');
                break;

            case 'empty':
                $db->transStart();
                $db->table('kanban_tasks')
                    ->where('column_id', (int) $column['id'])
                    ->where('is_archived', 0)
                    ->update(['is_archived' => 1]);
                $db->transComplete();
                $this->log(null, 'column_emptied', 'vació la columna «' . $column['name'] . '»');
                break;

            case 'delete':
                // Borrado definitivo: las FK en cascada se llevan las tareas y
                // todo lo que cuelga de ellas.
                $this->deleteColumnFiles((int) $column['id']);
                $columnModel->delete((int) $column['id']);
                $this->log(null, 'column_deleted', 'eliminó la columna «' . $column['name'] . '»');
                break;

            default:
                return $this->fail('Acción de columna desconocida.');
        }

        return $this->ok($this->boardPayload());
    }

    /** POST · nuevo orden horizontal de las columnas. */
    public function reorderColumns($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $order = $this->payload()['order'] ?? [];
        if (! is_array($order)) {
            return $this->fail('Orden inválido.');
        }

        (new KanbanColumnModel())->applyOrder((int) $this->board['id'], $order);

        return $this->ok($this->boardPayload());
    }

    /* =================================================================
       Tareas
       ================================================================= */

    public function createTask($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $data    = $this->payload();
        $boardId = (int) $this->board['id'];

        $column = (new KanbanColumnModel())->findInBoard((int) ($data['column_id'] ?? 0), $boardId);
        if ($column === null) {
            return $this->fail('Esa columna no existe en este tablero.', 404);
        }

        $title = $this->text($data['title'] ?? '', 200);
        if ($title === '') {
            return $this->fail('La tarea necesita un título.');
        }

        $boardModel = new KanbanBoardModel();
        $taskModel  = new KanbanTaskModel();

        $parentId = (int) ($data['parent_task_id'] ?? 0);
        if ($parentId > 0 && $taskModel->findInBoard($parentId, $boardId) === null) {
            return $this->fail('La tarea padre no existe en este tablero.', 404);
        }

        $taskId = $taskModel->insert([
            'board_id'       => $boardId,
            'column_id'      => (int) $column['id'],
            'parent_task_id' => $parentId > 0 ? $parentId : null,
            'code'           => $boardModel->nextTaskCode($boardId),
            'title'          => $title,
            'priority'       => in_array($data['priority'] ?? '', KanbanTaskModel::PRIORITIES, true)
                ? $data['priority']
                : 'media',
            'status'         => $column['status_key'],
            'position'       => $taskModel->nextPosition((int) $column['id']),
            'is_completed'   => (int) $column['is_final'],
            'completed_at'   => (int) $column['is_final'] === 1 ? date('Y-m-d H:i:s') : null,
            'created_by'     => (int) session('user_id'),
        ], true);

        if ($taskId === false) {
            return $this->fail('No se pudo crear la tarea.', 500);
        }

        $this->log((int) $taskId, 'task_created', 'creó la tarea «' . $title . '»');

        return $this->ok($this->boardPayload() + ['created_task_id' => (int) $taskId]);
    }

    /** GET · datos profundos de una tarea (los que usa el modal). */
    public function taskDetail($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        return $this->ok(['task' => $this->taskDetailPayload($task)]);
    }

    public function updateTask($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId   = (int) $this->board['id'];
        $taskModel = new KanbanTaskModel();
        $task = $taskModel->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $data    = $this->payload();
        $changes = [];
        $notes   = [];

        if (array_key_exists('title', $data)) {
            $title = $this->text($data['title'], 200);
            if ($title === '') {
                return $this->fail('El título de la tarea no puede quedar vacío.');
            }
            if ($title !== $task['title']) {
                $notes[] = 'cambió el título a «' . $title . '»';
            }
            $changes['title'] = $title;
        }

        if (array_key_exists('description', $data)) {
            $description = $this->text($data['description'], 5000);
            $changes['description'] = $description !== '' ? $description : null;
        }

        if (array_key_exists('internal_notes', $data)) {
            $internal = $this->text($data['internal_notes'], 5000);
            $changes['internal_notes'] = $internal !== '' ? $internal : null;
        }

        if (array_key_exists('color', $data)) {
            $color = (string) $data['color'];
            if ($color === '') {
                $changes['color'] = null;
            } elseif (! KanbanBoardModel::isValidColor($color)) {
                return $this->fail('Ese color no está en la paleta.');
            } else {
                $changes['color'] = $color;
            }
        }

        if (array_key_exists('priority', $data)) {
            if (! in_array($data['priority'], KanbanTaskModel::PRIORITIES, true)) {
                return $this->fail('Prioridad inválida.');
            }
            if ($data['priority'] !== $task['priority']) {
                $notes[] = 'cambió la prioridad a ' . $data['priority'];
            }
            $changes['priority'] = $data['priority'];
        }

        foreach (['start_date', 'due_date'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $date = $this->date($data[$field]);
            if ($date === false) {
                return $this->fail('Fecha inválida.');
            }
            if ($date !== ($task[$field] ?? null)) {
                $notes[] = $field === 'due_date' ? 'cambió la fecha límite' : 'cambió la fecha de inicio';
            }
            $changes[$field] = $date;
        }

        foreach (['estimated_minutes', 'logged_minutes'] as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if ($value === '' || $value === null) {
                $changes[$field] = $field === 'logged_minutes' ? 0 : null;
                continue;
            }
            $value = (int) $value;
            if ($value < 0 || $value > 100000) {
                return $this->fail('El tiempo tiene que estar entre 0 y 100000 minutos.');
            }
            $changes[$field] = $value;
        }

        if ($changes !== []) {
            $taskModel->update((int) $task['id'], $changes);
            foreach ($notes as $note) {
                $this->log((int) $task['id'], 'task_updated', $note);
            }
            if ($notes === []) {
                $this->log((int) $task['id'], 'task_updated', 'editó la tarea');
            }
        }

        // Se relee la fila antes de armar el detalle: si se devuelve la que
        // se leyó al principio, la ventana abierta sigue mostrando los
        // valores viejos (la prioridad, por ejemplo) aunque ya se guardaron.
        $fresh = $taskModel->findInBoard((int) $task['id'], $boardId) ?? $task;

        return $this->ok(['task' => $this->taskDetailPayload($fresh)] + $this->boardPayload());
    }

    /**
     * POST · mover una tarea a una columna y posición concretas.
     * Es el endpoint del drag and drop: además del orden, sincroniza el
     * estado según la columna destino y deja registro en el historial.
     */
    public function moveTask($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId     = (int) $this->board['id'];
        $taskModel   = new KanbanTaskModel();
        $columnModel = new KanbanColumnModel();

        $task = $taskModel->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $data   = $this->payload();
        $target = $columnModel->findInBoard((int) ($data['column_id'] ?? 0), $boardId);
        if ($target === null) {
            return $this->fail('Esa columna no existe en este tablero.', 404);
        }

        $origin   = $columnModel->findInBoard((int) $task['column_id'], $boardId);
        $position = max(0, (int) ($data['position'] ?? 0));

        $db = Database::connect();
        $db->transStart();

        // Hueco en el destino y compactado del origen: así las posiciones
        // quedan siempre 0..n sin agujeros ni empates.
        $taskModel->makeRoom((int) $target['id'], $position, (int) $task['id']);

        $changes = [
            'column_id' => (int) $target['id'],
            'position'  => $position,
        ];

        // El estado sigue a la columna cuando la columna define uno.
        if (! empty($target['status_key'])) {
            $changes['status'] = $target['status_key'];
        }

        // Columna final ⇒ se completa. Salir de una final ⇒ se reabre.
        if ((int) $target['is_final'] === 1 && (int) $task['is_completed'] === 0) {
            $changes['is_completed'] = 1;
            $changes['completed_at'] = date('Y-m-d H:i:s');
        } elseif ((int) $target['is_final'] === 0 && $origin !== null && (int) $origin['is_final'] === 1) {
            $changes['is_completed'] = 0;
            $changes['completed_at'] = null;
        }

        $taskModel->update((int) $task['id'], $changes);

        if ($origin !== null && (int) $origin['id'] !== (int) $target['id']) {
            $taskModel->resequence((int) $origin['id']);
        }
        $taskModel->resequence((int) $target['id']);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->fail('No se pudo guardar el movimiento.', 500);
        }

        $detail = $origin !== null && (int) $origin['id'] !== (int) $target['id']
            ? 'movió la tarea de «' . $origin['name'] . '» a «' . $target['name'] . '»'
            : 'reordenó la tarea en «' . $target['name'] . '»';
        $this->log((int) $task['id'], 'task_moved', $detail);

        return $this->ok($this->boardPayload());
    }

    /**
     * Acciones de tarea: completar, reabrir, archivar, restaurar, duplicar
     * y eliminar definitivamente.
     */
    public function taskAction($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId   = (int) $this->board['id'];
        $taskModel = new KanbanTaskModel();

        $task = $taskModel->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $action = (string) ($this->payload()['action'] ?? '');

        switch ($action) {
            case 'complete':
                $taskModel->update((int) $task['id'], [
                    'is_completed' => 1,
                    'completed_at' => date('Y-m-d H:i:s'),
                ]);
                $this->log((int) $task['id'], 'task_completed', 'marcó la tarea como completada');
                break;

            case 'reopen':
                $taskModel->update((int) $task['id'], [
                    'is_completed' => 0,
                    'completed_at' => null,
                ]);
                $this->log((int) $task['id'], 'task_reopened', 'reabrió la tarea');
                break;

            case 'archive':
                $taskModel->update((int) $task['id'], ['is_archived' => 1]);
                $taskModel->resequence((int) $task['column_id']);
                $this->log((int) $task['id'], 'task_archived', 'archivó la tarea');
                break;

            case 'restore':
                // Si la columna original se archivó, la tarea vuelve a la
                // primera columna visible para no quedar huérfana.
                $columnModel = new KanbanColumnModel();
                $column = $columnModel->findInBoard((int) $task['column_id'], $boardId);
                if ($column === null || (int) $column['is_archived'] === 1) {
                    $visible = $columnModel->activeForBoard($boardId);
                    if ($visible === []) {
                        return $this->fail('Creá una columna antes de restaurar la tarea.');
                    }
                    $column = $visible[0];
                }
                $taskModel->update((int) $task['id'], [
                    'is_archived' => 0,
                    'column_id'   => (int) $column['id'],
                    'position'    => $taskModel->nextPosition((int) $column['id']),
                ]);
                $this->log((int) $task['id'], 'task_restored', 'restauró la tarea');
                break;

            case 'duplicate':
                $newId = $this->cloneTask($task, (int) $task['column_id'], (int) $task['position'] + 1);
                if ($newId !== null) {
                    $taskModel->resequence((int) $task['column_id']);
                    $this->log($newId, 'task_duplicated', 'duplicó la tarea «' . $task['title'] . '»');
                }
                break;

            case 'delete':
                $this->deleteTaskFiles((int) $task['id']);
                $taskModel->delete((int) $task['id']);
                $taskModel->resequence((int) $task['column_id']);
                $this->log(null, 'task_deleted', 'eliminó la tarea «' . $task['title'] . '»');
                break;

            default:
                return $this->fail('Acción de tarea desconocida.');
        }

        return $this->ok($this->boardPayload());
    }

    /* =================================================================
       Responsables y etiquetas
       ================================================================= */

    public function toggleAssignee($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $userId = (int) ($this->payload()['user_id'] ?? 0);
        // Solo se puede asignar a gente que tiene acceso al espacio.
        if (! (new IndustryUserModel())->userBelongsTo($userId, (int) $this->device['industry_id'])) {
            return $this->fail('Ese usuario no pertenece al espacio.', 403);
        }

        $assigned = (new KanbanTaskAssigneeModel())->toggle((int) $task['id'], $userId);

        // Con el nombre entre paréntesis: en el historial «asignó un
        // responsable» a secas no dice a quién se le asignó la tarea.
        $name = '';
        foreach ($this->members() as $member) {
            if ($member['id'] === $userId) {
                $name = $member['nombre_completo'];
                break;
            }
        }

        $this->log(
            (int) $task['id'],
            'task_assignee_changed',
            ($assigned ? 'asignó' : 'quitó') . ' un responsable'
                . ($name !== '' ? ' (' . $name . ')' : '')
        );

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function createLabel($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $data = $this->payload();
        $name = $this->text($data['name'] ?? '', 60);
        if ($name === '') {
            return $this->fail('La etiqueta necesita un nombre.');
        }

        $labelModel = new KanbanLabelModel();
        $existing = $labelModel->where('board_id', (int) $this->board['id'])
            ->where('name', $name)
            ->first();
        if ($existing !== null) {
            return $this->fail('Ya existe una etiqueta con ese nombre.');
        }

        $color = (string) ($data['color'] ?? 'azul');
        $labelModel->insert([
            'board_id' => (int) $this->board['id'],
            'name'     => $name,
            'color'    => KanbanBoardModel::isValidColor($color) ? $color : 'azul',
        ]);

        return $this->ok($this->boardPayload());
    }

    public function updateLabel($deviceId = null, $labelId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $labelModel = new KanbanLabelModel();
        $label = $labelModel->findInBoard((int) $labelId, (int) $this->board['id']);
        if ($label === null) {
            return $this->fail('Esa etiqueta no existe en este tablero.', 404);
        }

        $data = $this->payload();

        if (($data['action'] ?? '') === 'delete') {
            $labelModel->delete((int) $label['id']);

            return $this->ok($this->boardPayload());
        }

        $changes = [];
        if (array_key_exists('name', $data)) {
            $name = $this->text($data['name'], 60);
            if ($name === '') {
                return $this->fail('La etiqueta necesita un nombre.');
            }
            $changes['name'] = $name;
        }
        if (array_key_exists('color', $data)) {
            if (! KanbanBoardModel::isValidColor((string) $data['color'])) {
                return $this->fail('Ese color no está en la paleta.');
            }
            $changes['color'] = (string) $data['color'];
        }

        if ($changes !== []) {
            $labelModel->update((int) $label['id'], $changes);
        }

        return $this->ok($this->boardPayload());
    }

    public function toggleLabel($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $labelId = (int) ($this->payload()['label_id'] ?? 0);
        if ((new KanbanLabelModel())->findInBoard($labelId, $boardId) === null) {
            return $this->fail('Esa etiqueta no existe en este tablero.', 404);
        }

        (new KanbanTaskLabelModel())->toggle((int) $task['id'], $labelId);

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    /* =================================================================
       Checklists
       ================================================================= */

    public function createChecklist($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $checklistModel = new KanbanChecklistModel();
        $title = $this->text($this->payload()['title'] ?? '', 150) ?: 'Checklist';

        $checklistModel->insert([
            'task_id'  => (int) $task['id'],
            'title'    => $title,
            'position' => $checklistModel->nextPosition((int) $task['id']),
        ]);

        $this->log((int) $task['id'], 'checklist_created', 'agregó el checklist «' . $title . '»');

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function updateChecklist($deviceId = null, $checklistId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId        = (int) $this->board['id'];
        $checklistModel = new KanbanChecklistModel();
        $checklist = $checklistModel->findInBoard((int) $checklistId, $boardId);
        if ($checklist === null) {
            return $this->fail('Ese checklist no existe en este tablero.', 404);
        }

        $task = (new KanbanTaskModel())->findInBoard((int) $checklist['task_id'], $boardId);
        $data = $this->payload();

        if (($data['action'] ?? '') === 'delete') {
            $checklistModel->delete((int) $checklist['id']);
            $this->log((int) $checklist['task_id'], 'checklist_deleted', 'eliminó un checklist');

            return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
        }

        if (array_key_exists('title', $data)) {
            $title = $this->text($data['title'], 150);
            if ($title === '') {
                return $this->fail('El checklist necesita un título.');
            }
            $checklistModel->update((int) $checklist['id'], ['title' => $title]);
        }

        if (isset($data['order']) && is_array($data['order'])) {
            (new KanbanChecklistItemModel())->applyOrder((int) $checklist['id'], $data['order']);
        }

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function createChecklistItem($deviceId = null, $checklistId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $checklist = (new KanbanChecklistModel())->findInBoard((int) $checklistId, $boardId);
        if ($checklist === null) {
            return $this->fail('Ese checklist no existe en este tablero.', 404);
        }

        $content = $this->text($this->payload()['content'] ?? '', 255);
        if ($content === '') {
            return $this->fail('El ítem necesita un texto.');
        }

        $itemModel = new KanbanChecklistItemModel();
        $itemModel->insert([
            'checklist_id' => (int) $checklist['id'],
            'content'      => $content,
            'position'     => $itemModel->nextPosition((int) $checklist['id']),
        ]);

        $task = (new KanbanTaskModel())->findInBoard((int) $checklist['task_id'], $boardId);
        $this->log((int) $checklist['task_id'], 'checklist_updated', 'agregó un ítem al checklist');

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function updateChecklistItem($deviceId = null, $itemId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId   = (int) $this->board['id'];
        $itemModel = new KanbanChecklistItemModel();
        $item = $itemModel->findInBoard((int) $itemId, $boardId);
        if ($item === null) {
            return $this->fail('Ese ítem no existe en este tablero.', 404);
        }

        $taskModel = new KanbanTaskModel();
        $task = $taskModel->findInBoard((int) $item['task_id'], $boardId);
        $data = $this->payload();

        if (($data['action'] ?? '') === 'delete') {
            $itemModel->delete((int) $item['id']);

            return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
        }

        // Convertir el ítem en una tarea independiente de la misma columna.
        if (($data['action'] ?? '') === 'promote') {
            $boardModel = new KanbanBoardModel();
            $newId = $taskModel->insert([
                'board_id'   => $boardId,
                'column_id'  => (int) $task['column_id'],
                'code'       => $boardModel->nextTaskCode($boardId),
                'title'      => $this->text($item['content'], 200),
                'priority'   => 'media',
                'status'     => $task['status'],
                'position'   => $taskModel->nextPosition((int) $task['column_id']),
                'created_by' => (int) session('user_id'),
            ], true);

            if ($newId !== false) {
                $itemModel->delete((int) $item['id']);
                $this->log((int) $newId, 'task_created', 'convirtió un ítem de checklist en tarea');
            }

            return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
        }

        $changes = [];
        if (array_key_exists('content', $data)) {
            $content = $this->text($data['content'], 255);
            if ($content === '') {
                return $this->fail('El ítem no puede quedar vacío.');
            }
            $changes['content'] = $content;
        }
        if (array_key_exists('is_completed', $data)) {
            $changes['is_completed'] = $this->flag($data['is_completed']);
        }

        if ($changes !== []) {
            $itemModel->update((int) $item['id'], $changes);
        }

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    /* =================================================================
       Comentarios
       ================================================================= */

    public function createComment($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $data    = $this->payload();
        $content = $this->text($data['content'] ?? '', 3000);
        if ($content === '') {
            return $this->fail('El comentario está vacío.');
        }

        $commentModel = new KanbanCommentModel();

        $parentId = (int) ($data['parent_comment_id'] ?? 0);
        if ($parentId > 0 && $commentModel->findInBoard($parentId, $boardId) === null) {
            return $this->fail('El comentario al que respondés no existe.', 404);
        }

        $commentModel->insert([
            'task_id'           => (int) $task['id'],
            'user_id'           => (int) session('user_id'),
            'parent_comment_id' => $parentId > 0 ? $parentId : null,
            'content'           => $content,
        ]);

        $this->log((int) $task['id'], 'comment_created', 'comentó la tarea');

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function updateComment($deviceId = null, $commentId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId      = (int) $this->board['id'];
        $commentModel = new KanbanCommentModel();
        $comment = $commentModel->findInBoard((int) $commentId, $boardId);
        if ($comment === null) {
            return $this->fail('Ese comentario no existe en este tablero.', 404);
        }

        // Un comentario solo lo edita o borra su autor.
        if ((int) $comment['user_id'] !== (int) session('user_id')) {
            return $this->fail('Solo el autor puede editar o eliminar su comentario.', 403);
        }

        $task = (new KanbanTaskModel())->findInBoard((int) $comment['task_id'], $boardId);
        $data = $this->payload();

        if (($data['action'] ?? '') === 'delete') {
            $commentModel->delete((int) $comment['id']);

            return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
        }

        $content = $this->text($data['content'] ?? '', 3000);
        if ($content === '') {
            return $this->fail('El comentario está vacío.');
        }

        $commentModel->update((int) $comment['id'], [
            'content'   => $content,
            'edited_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    /* =================================================================
       Enlaces y adjuntos
       ================================================================= */

    public function createLink($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $data = $this->payload();
        $url  = $this->text($data['url'] ?? '', 500);
        if ($url === '') {
            return $this->fail('Pegá la dirección del enlace.');
        }
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return $this->fail('Esa dirección no es válida.');
        }

        (new KanbanLinkModel())->insert([
            'task_id' => (int) $task['id'],
            'title'   => $this->text($data['title'] ?? '', 150) ?: null,
            'url'     => $url,
        ]);

        $this->log((int) $task['id'], 'link_added', 'agregó un enlace');

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function deleteLink($deviceId = null, $linkId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId   = (int) $this->board['id'];
        $linkModel = new KanbanLinkModel();
        $link = $linkModel->findInBoard((int) $linkId, $boardId);
        if ($link === null) {
            return $this->fail('Ese enlace no existe en este tablero.', 404);
        }

        $task = (new KanbanTaskModel())->findInBoard((int) $link['task_id'], $boardId);
        $linkModel->delete((int) $link['id']);

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function uploadAttachment($deviceId = null, $taskId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $task = (new KanbanTaskModel())->findInBoard((int) $taskId, $boardId);
        if ($task === null) {
            return $this->fail('Esa tarea no existe en este tablero.', 404);
        }

        $file = $this->request->getFile('file');
        if ($file === null || ! $file->isValid()) {
            return $this->fail('No llegó ningún archivo válido.');
        }

        if ($file->getSize() > self::MAX_UPLOAD_BYTES) {
            return $this->fail('El archivo supera los 5 MB.');
        }

        $extension = strtolower($file->getExtension() ?: '');
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return $this->fail('Ese tipo de archivo no está permitido.');
        }

        $directory = FCPATH . 'uploads/kanban/' . $boardId;
        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return $this->fail('No se pudo preparar la carpeta de adjuntos.', 500);
        }

        // Nombre aleatorio: el nombre original solo se guarda para mostrar.
        $storedName = $file->getRandomName();
        try {
            $file->move($directory, $storedName);
        } catch (\Throwable $e) {
            return $this->fail('No se pudo guardar el archivo.', 500);
        }

        (new KanbanAttachmentModel())->insert([
            'task_id'   => (int) $task['id'],
            'user_id'   => (int) session('user_id'),
            'file_name' => $this->text($file->getClientName(), 255),
            'file_path' => $boardId . '/' . $storedName,
            'file_size' => (int) @filesize($directory . DIRECTORY_SEPARATOR . $storedName),
            'mime_type' => $file->getClientMimeType(),
            'is_image'  => in_array($extension, self::IMAGE_EXTENSIONS, true) ? 1 : 0,
        ]);

        $this->log((int) $task['id'], 'attachment_added', 'adjuntó un archivo');

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    public function deleteAttachment($deviceId = null, $attachmentId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId         = (int) $this->board['id'];
        $attachmentModel = new KanbanAttachmentModel();
        $attachment = $attachmentModel->findInBoard((int) $attachmentId, $boardId);
        if ($attachment === null) {
            return $this->fail('Ese adjunto no existe en este tablero.', 404);
        }

        $task = (new KanbanTaskModel())->findInBoard((int) $attachment['task_id'], $boardId);

        $this->unlinkUpload($attachment['file_path'] ?? '');
        $attachmentModel->delete((int) $attachment['id']);

        return $this->ok(['task' => $this->taskDetailPayload($task)] + $this->boardPayload());
    }

    /* =================================================================
       Paneles auxiliares
       ================================================================= */

    /** GET · columnas y tareas archivadas, para restaurarlas. */
    public function archive($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];

        return $this->ok([
            'columns' => array_map(
                fn ($column) => $this->columnPayload($column),
                (new KanbanColumnModel())->archivedForBoard($boardId)
            ),
            'tasks' => array_map(
                fn ($task) => $this->taskCardPayload($task),
                (new KanbanTaskModel())->archivedForBoard($boardId)
            ),
        ]);
    }

    /** GET · tareas completadas con búsqueda, para el panel inferior. */
    public function completed($deviceId = null)
    {
        if (($error = $this->resolve((int) $deviceId)) !== null) {
            return $error;
        }

        $boardId = (int) $this->board['id'];
        $search  = $this->text($this->request->getGet('q') ?? '', 120);

        $tasks     = (new KanbanTaskModel())->completedForBoard($boardId, $search);
        $taskIds   = array_map('intval', array_column($tasks, 'id'));
        $images    = (new KanbanAttachmentModel())->imagesForTasks($taskIds);
        $assignees = (new KanbanTaskAssigneeModel())->assigneesByTask($boardId);
        $columns   = array_column((new KanbanColumnModel())->activeForBoard($boardId), 'name', 'id');

        $rows = [];
        foreach ($tasks as $task) {
            $id = (int) $task['id'];
            $rows[] = [
                'id'           => $id,
                'code'         => $task['code'],
                'title'        => $task['title'],
                'description'  => $task['description'],
                'color'        => $task['color'],
                'priority'     => $task['priority'],
                'column_name'  => $columns[(int) $task['column_id']] ?? null,
                'completed_at' => $task['completed_at'],
                'assignees'    => $this->withInitials($assignees[$id] ?? []),
                'images'       => array_map(fn ($image) => [
                    'id'   => (int) $image['id'],
                    'name' => $image['file_name'],
                    'url'  => base_url('uploads/kanban/' . $image['file_path']),
                ], $images[$id] ?? []),
            ];
        }

        return $this->ok(['tasks' => $rows, 'query' => $search]);
    }

    /* =================================================================
       Scope y helpers de respuesta
       ================================================================= */

    /**
     * Resuelve placa + tablero y verifica la membresía del usuario.
     * Devuelve null si todo está bien, o la respuesta de error si no.
     */
    private function resolve(int $deviceId): ?ResponseInterface
    {
        if ($deviceId <= 0) {
            return $this->fail('Placa inválida.', 404);
        }

        $device = (new Esp32DeviceModel())->find($deviceId);
        if ($device === null) {
            return $this->fail('Esa placa no existe.', 404);
        }

        $userId = (int) session('user_id');
        $industryId = (int) $device['industry_id'];
        $membershipModel = new IndustryUserModel();
        $membership = $membershipModel->membershipFor($userId, $industryId);
        if ($membership === null) {
            return $this->fail('No tenés acceso a esa placa.', 403);
        }

        if (! $membershipModel->canSeeAllDevices($userId, $industryId)) {
            $allowedIds = (new DeviceAccessModel())->allowedDeviceIdsForMembership((int) $membership['id']);
            if (! in_array((int) $device['id'], $allowedIds, true)) {
                return $this->fail('No tenés acceso a esa placa.', 403);
            }
        }

        $this->device = $device;
        $this->board  = (new KanbanBoardModel())->forDevice($deviceId, $userId);

        if ($this->board === []) {
            return $this->fail('No se pudo abrir el tablero de la placa.', 500);
        }

        return null;
    }

    /**
     * Payload completo del tablero. Todos los endpoints que modifican algo lo
     * devuelven, así el cliente nunca queda desincronizado de la base.
     */
    private function boardPayload(): array
    {
        $boardId = (int) $this->board['id'];

        $columns = (new KanbanColumnModel())->activeForBoard($boardId);
        $tasks   = (new KanbanTaskModel())->activeForBoard($boardId);

        $labelsByTask    = (new KanbanLabelModel())->labelIdsByTask($boardId);
        $assigneesByTask = (new KanbanTaskAssigneeModel())->assigneesByTask($boardId);
        $checklistByTask = (new KanbanChecklistModel())->progressByTask($boardId);
        $commentCounts   = (new KanbanCommentModel())->countsByTask($boardId);
        $attachCounts    = (new KanbanAttachmentModel())->countsByTask($boardId);

        $cards = [];
        foreach ($tasks as $task) {
            $id = (int) $task['id'];
            $cards[] = $this->taskCardPayload($task) + [
                'labels'      => $labelsByTask[$id] ?? [],
                'assignees'   => $this->withInitials($assigneesByTask[$id] ?? []),
                'checklist'   => $checklistByTask[$id] ?? ['done' => 0, 'total' => 0],
                'comments'    => $commentCounts[$id] ?? 0,
                'attachments' => $attachCounts[$id] ?? 0,
            ];
        }

        return [
            'board' => [
                'id'               => $boardId,
                'name'             => $this->board['name'],
                'description'      => $this->board['description'],
                'background_color' => $this->board['background_color'],
            ],
            'columns'  => array_map(fn ($column) => $this->columnPayload($column), $columns),
            'tasks'    => $cards,
            'labels'   => array_map(fn ($label) => [
                'id'    => (int) $label['id'],
                'name'  => $label['name'],
                'color' => $label['color'],
            ], (new KanbanLabelModel())->forBoard($boardId)),
            'members'  => $this->members(),
            'colors'   => KanbanBoardModel::COLORS,
            'user_id'  => (int) session('user_id'),
            'today'    => date('Y-m-d'),
        ];
    }

    private function columnPayload(array $column): array
    {
        return [
            'id'           => (int) $column['id'],
            'name'         => $column['name'],
            'description'  => $column['description'],
            'color'        => $column['color'],
            'position'     => (int) $column['position'],
            'status_key'   => $column['status_key'],
            'is_final'     => (int) $column['is_final'] === 1,
            'task_limit'   => $column['task_limit'] !== null ? (int) $column['task_limit'] : null,
            'is_collapsed' => (int) $column['is_collapsed'] === 1,
            'is_archived'  => (int) $column['is_archived'] === 1,
        ];
    }

    private function taskCardPayload(array $task): array
    {
        return [
            'id'                => (int) $task['id'],
            'column_id'         => (int) $task['column_id'],
            'parent_task_id'    => $task['parent_task_id'] !== null ? (int) $task['parent_task_id'] : null,
            'code'              => $task['code'],
            'title'             => $task['title'],
            'description'       => $task['description'],
            'color'             => $task['color'],
            'priority'          => $task['priority'],
            'status'            => $task['status'],
            'position'          => (int) $task['position'],
            'start_date'        => $task['start_date'],
            'due_date'          => $task['due_date'],
            'completed_at'      => $task['completed_at'],
            'estimated_minutes' => $task['estimated_minutes'] !== null ? (int) $task['estimated_minutes'] : null,
            'logged_minutes'    => (int) $task['logged_minutes'],
            'is_completed'      => (int) $task['is_completed'] === 1,
            'is_archived'       => (int) $task['is_archived'] === 1,
            'updated_at'        => $task['updated_at'],
            'created_at'        => $task['created_at'],
        ];
    }

    /**
     * Datos profundos de una tarea: lo que necesita el modal de detalle.
     */
    private function taskDetailPayload(?array $task): ?array
    {
        if ($task === null) {
            return null;
        }

        $taskId  = (int) $task['id'];
        $boardId = (int) $this->board['id'];

        $checklistModel = new KanbanChecklistModel();
        $checklists = $checklistModel->forTask($taskId);
        $items = (new KanbanChecklistItemModel())->forChecklists(
            array_map('intval', array_column($checklists, 'id'))
        );

        $itemsByChecklist = [];
        foreach ($items as $item) {
            $itemsByChecklist[(int) $item['checklist_id']][] = [
                'id'           => (int) $item['id'],
                'content'      => $item['content'],
                'is_completed' => (int) $item['is_completed'] === 1,
                'position'     => (int) $item['position'],
            ];
        }

        $comments = array_map(fn ($comment) => [
            'id'        => (int) $comment['id'],
            'parent_id' => $comment['parent_comment_id'] !== null ? (int) $comment['parent_comment_id'] : null,
            'user_id'   => $comment['user_id'] !== null ? (int) $comment['user_id'] : null,
            'author'    => trim(($comment['nombre'] ?? '') . ' ' . ($comment['apellido'] ?? '')) ?: 'Usuario eliminado',
            'initials'  => $this->initials($comment['nombre'] ?? '', $comment['apellido'] ?? ''),
            'content'   => $comment['content'],
            'edited_at' => $comment['edited_at'],
            'created_at' => $comment['created_at'],
        ], (new KanbanCommentModel())->forTask($taskId));

        $attachments = array_map(fn ($attachment) => [
            'id'       => (int) $attachment['id'],
            'name'     => $attachment['file_name'],
            'url'      => base_url('uploads/kanban/' . $attachment['file_path']),
            'size'     => (int) $attachment['file_size'],
            'is_image' => (int) $attachment['is_image'] === 1,
            'created_at' => $attachment['created_at'],
        ], (new KanbanAttachmentModel())->forTask($taskId));

        $subtasks = array_map(fn ($subtask) => [
            'id'           => (int) $subtask['id'],
            'code'         => $subtask['code'],
            'title'        => $subtask['title'],
            'is_completed' => (int) $subtask['is_completed'] === 1,
        ], (new KanbanTaskModel())
            ->where('parent_task_id', $taskId)
            ->where('board_id', $boardId)
            ->orderBy('position', 'ASC')
            ->findAll());

        $activity = array_map(fn ($entry) => [
            'id'      => (int) $entry['id'],
            'action'  => $entry['action'],
            'details' => $entry['details'],
            'author'  => trim(($entry['nombre'] ?? '') . ' ' . ($entry['apellido'] ?? '')) ?: 'Sistema',
            'created_at' => $entry['created_at'],
        ], (new KanbanActivityModel())->forTask($taskId));

        return $this->taskCardPayload($task) + [
            'internal_notes' => $task['internal_notes'],
            'labels'         => (new KanbanTaskLabelModel())->labelIdsFor($taskId),
            'assignee_ids'   => (new KanbanTaskAssigneeModel())->userIdsFor($taskId),
            'checklists'     => array_map(fn ($checklist) => [
                'id'    => (int) $checklist['id'],
                'title' => $checklist['title'],
                'items' => $itemsByChecklist[(int) $checklist['id']] ?? [],
            ], $checklists),
            'comments_list' => $comments,
            'attachments_list' => $attachments,
            'links'         => array_map(fn ($link) => [
                'id'    => (int) $link['id'],
                'title' => $link['title'],
                'url'   => $link['url'],
            ], (new KanbanLinkModel())->forTask($taskId)),
            'subtasks' => $subtasks,
            'activity' => $activity,
        ];
    }

    /** Miembros del espacio: alimentan el selector de responsables. */
    private function members(): array
    {
        $members = (new IndustryUserModel())->membersForIndustry((int) $this->device['industry_id']);

        return array_map(fn ($member) => [
            'id'       => (int) $member['user_id'],
            'nombre'   => $member['nombre'],
            'apellido' => $member['apellido'],
            'nombre_completo' => trim($member['nombre'] . ' ' . $member['apellido']),
            'initials' => $this->initials($member['nombre'], $member['apellido']),
            'role'     => $member['role'],
        ], $members);
    }

    private function withInitials(array $people): array
    {
        return array_map(fn ($person) => [
            'id'       => (int) $person['id'],
            'nombre'   => $person['nombre'],
            'apellido' => $person['apellido'],
            'nombre_completo' => trim($person['nombre'] . ' ' . $person['apellido']),
            'initials' => $this->initials($person['nombre'], $person['apellido']),
        ], $people);
    }

    private function initials(string $nombre, string $apellido): string
    {
        $first = mb_substr(trim($nombre), 0, 1);
        $last  = mb_substr(trim($apellido), 0, 1);

        return mb_strtoupper($first . $last) ?: '?';
    }

    /* =================================================================
       Utilidades internas
       ================================================================= */

    /** Copia una tarea (sin comentarios ni historial) dentro del tablero. */
    private function cloneTask(array $task, int $columnId, int $position): ?int
    {
        $taskModel  = new KanbanTaskModel();
        $boardModel = new KanbanBoardModel();
        $boardId    = (int) $this->board['id'];

        $taskModel->makeRoom($columnId, $position);

        $newId = $taskModel->insert([
            'board_id'          => $boardId,
            'column_id'         => $columnId,
            'parent_task_id'    => $task['parent_task_id'],
            'code'              => $boardModel->nextTaskCode($boardId),
            'title'             => $this->text($task['title'], 200),
            'description'       => $task['description'],
            'color'             => $task['color'],
            'priority'          => $task['priority'],
            'status'            => $task['status'],
            'position'          => $position,
            'start_date'        => $task['start_date'],
            'due_date'          => $task['due_date'],
            'estimated_minutes' => $task['estimated_minutes'],
            'internal_notes'    => $task['internal_notes'],
            'created_by'        => (int) session('user_id'),
        ], true);

        if ($newId === false) {
            return null;
        }

        $newId = (int) $newId;

        // Se copian etiquetas, responsables y checklists; los comentarios y el
        // historial pertenecen a la tarea original y no se duplican.
        foreach ((new KanbanTaskLabelModel())->labelIdsFor((int) $task['id']) as $labelId) {
            (new KanbanTaskLabelModel())->insert(['task_id' => $newId, 'label_id' => $labelId]);
        }
        foreach ((new KanbanTaskAssigneeModel())->userIdsFor((int) $task['id']) as $userId) {
            (new KanbanTaskAssigneeModel())->insert(['task_id' => $newId, 'user_id' => $userId]);
        }

        $checklistModel = new KanbanChecklistModel();
        $itemModel      = new KanbanChecklistItemModel();
        foreach ($checklistModel->forTask((int) $task['id']) as $checklist) {
            $newChecklistId = $checklistModel->insert([
                'task_id'  => $newId,
                'title'    => $checklist['title'],
                'position' => (int) $checklist['position'],
            ], true);

            if ($newChecklistId === false) {
                continue;
            }

            foreach ($itemModel->forChecklists([(int) $checklist['id']]) as $item) {
                $itemModel->insert([
                    'checklist_id' => (int) $newChecklistId,
                    'content'      => $item['content'],
                    'is_completed' => (int) $item['is_completed'],
                    'position'     => (int) $item['position'],
                ]);
            }
        }

        return $newId;
    }

        /**
     * Borra del tablero las tareas completadas hace más de COMPLETED_TTL_DAYS
     * días, junto con sus adjuntos en disco. Deja un único registro en el
     * historial con el total, para no ensuciar la actividad del tablero.
     *
     * Si no hay nada vencido, no toca la base.
     */
    private function purgeExpiredCompleted(int $boardId): void
    {
        $taskModel = new KanbanTaskModel();
        $expired   = $taskModel->expiredCompletedForBoard($boardId, self::COMPLETED_TTL_DAYS);

        if ($expired === []) {
            return;
        }

        $db = Database::connect();
        $db->transStart();

        // Primero los archivos en disco, después las filas: si algo falla
        // al borrar la fila, el adjunto ya no está huérfano en la carpeta.
        foreach ($expired as $task) {
            $this->deleteTaskFiles((int) $task['id']);
        }

        $ids = array_map('intval', array_column($expired, 'id'));
        $db->table('kanban_tasks')->whereIn('id', $ids)->delete();

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'No se pudieron purgar tareas vencidas del tablero {board}', [
                'board' => $boardId,
            ]);

            return;
        }

        $this->log(
            null,
            'tasks_purged',
            'borró ' . count($expired) . ' tarea(s) completada(s) hace más de '
                . self::COMPLETED_TTL_DAYS . ' días'
        );
    }


    /** Borra del disco los adjuntos de una tarea antes de eliminarla. */
    private function deleteTaskFiles(int $taskId): void
    {
        foreach ((new KanbanAttachmentModel())->forTask($taskId) as $attachment) {
            $this->unlinkUpload($attachment['file_path'] ?? '');
        }
    }

    /** Ídem para todas las tareas de una columna. */
    private function deleteColumnFiles(int $columnId): void
    {
        $tasks = (new KanbanTaskModel())->select('id')->where('column_id', $columnId)->findAll();
        foreach ($tasks as $task) {
            $this->deleteTaskFiles((int) $task['id']);
        }
    }

    /**
     * Borra un adjunto del disco. Solo acepta rutas con la forma
     * "<board_id>/<archivo>" para que un dato corrupto no pueda apuntar
     * fuera de la carpeta de subidas.
     */
    private function unlinkUpload(string $relativePath): void
    {
        if (! preg_match('~^\d+/[A-Za-z0-9_.-]+$~', $relativePath)) {
            return;
        }

        $full = FCPATH . 'uploads/kanban/' . $relativePath;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    private function log(?int $taskId, string $action, string $details): void
    {
        (new KanbanActivityModel())->log(
            (int) $this->board['id'],
            $taskId,
            (int) session('user_id'),
            $action,
            $details
        );
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

    /** Normaliza texto libre: recorta, limita y saca caracteres de control. */
    private function text($value, int $max): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value) ?? '';

        return mb_substr(trim($value), 0, $max);
    }

    private function flag($value): int
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /**
     * Valida una fecha Y-m-d. Devuelve null si viene vacía y false si es
     * inválida (para distinguir "borrar la fecha" de "fecha mal formada").
     */
    private function date($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;
        $parsed = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $parsed !== false && $parsed->format('Y-m-d') === $value ? $value : false;
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
