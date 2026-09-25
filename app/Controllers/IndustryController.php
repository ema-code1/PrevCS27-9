<?php

namespace App\Controllers;

use App\Libraries\SpaceModalRegions;
use App\Models\DeviceAccessModel;
use App\Models\Esp32DeviceModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SpaceActionTokenModel;
use App\Models\SpaceInvitationModel;
use App\Models\UserModel;

class IndustryController extends BaseController
{
    private const JOIN_ERROR = 'El ID o la contraseña del espacio no son correctos.';

    /* ── Respuestas del modal de configuración ────────────────────────

       Todas las acciones del modal se mandan por fetch. Antes cada una
       terminaba en un redirect: el navegador recargaba la página entera,
       la ventana se cerraba y se volvía a abrir sola por el ?config=<id>,
       y en el medio se perdía el scroll y lo que estuviera a medio
       escribir. Ahora el controlador contesta JSON con las regiones ya
       dibujadas de nuevo, y el navegador solo reemplaza lo que cambió.

       Se mantiene el redirect para un POST normal (sin JavaScript): las
       mismas acciones siguen funcionando si el fetch no está disponible. */

    private function wantsJson(): bool
    {
        return $this->request->isAJAX();
    }

    /**
     * @param list<string> $regions Qué partes del modal volver a dibujar.
     */
    private function jsonOk(int $industryId, string $message, array $regions = [], array $patch = [])
    {
        return $this->response->setJSON([
            'ok'      => true,
            'message' => $message,
            'csrf'    => csrf_hash(),
            'regions' => $this->renderRegions($industryId, $regions),
            'patch'   => $patch,
        ]);
    }

    private function jsonError(string $message, int $status = 422)
    {
        return $this->response->setStatusCode($status)->setJSON([
            'ok'      => false,
            'message' => $message,
            'csrf'    => csrf_hash(),
        ]);
    }

    private function renderRegions(int $industryId, array $regions): array
    {
        return (new SpaceModalRegions())->render($industryId, $regions);
    }

    /**
     * Formulario para unirse a una industria existente.
     */
    public function joinForm()
    {
        return view('onboarding/unirse-espacio', [
            'title'           => 'Unirse a un espacio | Preventive Control System',
            'hideAuthButtons' => true,
            'errors'          => session('errors') ?? [],
        ]);
    }

    /**
     * Valida ID + contraseña del espacio y agrega al usuario como supervisor.
     */
    public function join()
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return redirect()->to(base_url('reg-log'));
        }

        $rules = [
            'industry_uid'   => 'required|max_length[40]',
            'space_password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('unirse-espacio'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $uid      = trim((string) $this->request->getPost('industry_uid'));
        $password = (string) $this->request->getPost('space_password');

        // Freno a la fuerza bruta sobre la clave del espacio: 5 intentos por minuto.
        $throttleKey = 'join-' . md5($this->request->getIPAddress() . '|' . $userId);
        if (! service('throttler')->check($throttleKey, 5, MINUTE)) {
            return redirect()->to(base_url('unirse-espacio'))
                ->withInput()
                ->with('error', 'Demasiados intentos. Esperá un minuto y volvé a probar.');
        }

        $industryModel = new IndustryModel();
        $industry      = $industryModel->findByUid($uid);

        // Mensaje genérico: no revelamos si el UID existe o si falló el password.
        if ($industry === null || ! password_verify($password, (string) $industry['space_password_hash'])) {
            return redirect()->to(base_url('unirse-espacio'))
                ->withInput()
                ->with('error', self::JOIN_ERROR);
        }

        $membershipModel = new IndustryUserModel();

        if ($membershipModel->userBelongsTo($userId, (int) $industry['id'])) {
            // Ya pertenece: lo mandamos al dashboard con su industria activa.
            session()->set([
                'active_industry_id'   => (int) $industry['id'],
                'active_industry_role' => $membershipModel->roleFor($userId, (int) $industry['id']),
            ]);

            return redirect()->to(base_url('principal'))
                ->with('success', 'Ya pertenecías a este espacio.');
        }

        $membershipModel->addMembership($userId, (int) $industry['id'], IndustryUserModel::ROLE_SUPERVISOR);

        session()->set([
            'active_industry_id'   => (int) $industry['id'],
            'active_industry_role' => IndustryUserModel::ROLE_SUPERVISOR,
        ]);

        return redirect()->to(base_url('principal'))
            ->with('success', 'Te uniste al espacio como supervisor.');
    }

    public function update($industryId = null)
    {
        $userId = (int) session('user_id');
        $industryId = (int) $industryId;

        if (! $this->isAdmin($userId, $industryId)) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenés permiso para editar ese espacio.');
        }

        $rules = [
            'current_password' => 'required',
            'company_name'     => 'required|min_length[2]|max_length[200]',
            'space_name'       => 'required|min_length[2]|max_length[200]',
            'sector'           => 'permit_empty|max_length[120]',
            'space_password'   => 'permit_empty|min_length[6]|max_length[200]',
        ];

        if (! $this->validate($rules)) {
            $errors = implode(' ', $this->validator->getErrors());

            if ($this->wantsJson()) {
                return $this->jsonError($errors);
            }

            return redirect()->to(base_url('registros-iot?config=' . $industryId))
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('editing_industry_id', $industryId);
        }

        // Sin la clave actual del espacio no se actualiza nada.
        $industry = (new IndustryModel())->find($industryId);
        $current  = (string) $this->request->getPost('current_password');
        if ($industry === null || ! password_verify($current, (string) $industry['space_password_hash'])) {
            $message = 'La clave actual del espacio es incorrecta. No se guardaron los cambios.';

            if ($this->wantsJson()) {
                return $this->jsonError($message);
            }

            return redirect()->to(base_url('registros-iot?config=' . $industryId))
                ->withInput()
                ->with('error', $message);
        }

        $payload = [
            'company_name' => trim((string) $this->request->getPost('company_name')),
            'space_name'   => trim((string) $this->request->getPost('space_name')),
            'sector'       => trim((string) $this->request->getPost('sector')) ?: null,
        ];

        $newPassword = trim((string) $this->request->getPost('space_password'));
        if ($newPassword !== '') {
            $payload['space_password_hash'] = $newPassword;
        }

        (new IndustryModel())->update($industryId, $payload);

        if ($this->wantsJson()) {
            // La tarjeta del acordeón muestra los mismos datos: se actualiza
            // en el lugar para no dejarla contando la versión vieja.
            $subtitle = $payload['space_name'] . ($payload['sector'] !== null ? ' - ' . $payload['sector'] : '');

            return $this->jsonOk($industryId, 'Espacio actualizado.', [], [
                '[data-space-company="' . $industryId . '"]' => $payload['company_name'],
                '[data-space-subtitle="' . $industryId . '"]' => $subtitle,
                '[data-space-detail-company="' . $industryId . '"]' => $payload['company_name'],
                '[data-space-detail-name="' . $industryId . '"]' => $payload['space_name'],
                '[data-space-detail-sector="' . $industryId . '"]' => $payload['sector'] ?? 'Sin sector',
                '[data-space-input-company="' . $industryId . '"]' => $payload['company_name'],
                '[data-space-input-name="' . $industryId . '"]' => $payload['space_name'],
                '[data-space-input-sector="' . $industryId . '"]' => $payload['sector'] ?? '',
            ]);
        }

        return redirect()->to(base_url('registros-iot'))->with('success', 'Espacio actualizado.');
    }

    public function removeMember($industryId = null, $membershipId = null)
    {
        $userId = (int) session('user_id');
        $industryId = (int) $industryId;
        $membershipId = (int) $membershipId;

        if (! $this->isAdmin($userId, $industryId)) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenés permiso para administrar ese espacio.');
        }

        $membershipModel = new IndustryUserModel();
        $membership = $membershipModel->where('id', $membershipId)->where('industry_id', $industryId)->first();

        if ($membership === null) {
            return $this->fail($industryId, 'Ese acceso ya no existe.');
        }

        if ((int) $membership['user_id'] === $userId) {
            return $this->fail($industryId, 'No podés quitarte a vos mismo del espacio.');
        }

        // Un espacio sin admin queda huérfano: nadie podría volver a configurarlo.
        if ($membership['role'] === IndustryUserModel::ROLE_ADMIN && $this->adminCount($industryId) <= 1) {
            return $this->fail($industryId, 'Es el único admin del espacio. Nombrá otro admin antes de quitarlo.');
        }

        $membershipModel->delete($membershipId);

        $message = 'Acceso eliminado. Por seguridad, te recomendamos cambiar la contraseña del espacio.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['members']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    /**
     * Error de una acción del modal: JSON si vino por fetch, y si no el
     * redirect de siempre.
     */
    private function fail(int $industryId, string $message)
    {
        if ($this->wantsJson()) {
            return $this->jsonError($message);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('error', $message);
    }

    /**
     * Cambia el acceso de un miembro: rol (admin | supervisor) y, cuando es
     * supervisor, con qué permiso.
     *
     * Las dos cosas viajan juntas porque en el menú se eligen en un solo
     * gesto: "Supervisor" abre al costado y ahí se toca "Solo lectura" o
     * "Puede editar". Ser supervisor y con qué alcance es una decisión sola,
     * no dos, y resolverla en un request evita el estado intermedio en el que
     * alguien queda supervisor con el permiso que traía de antes.
     */
    public function updateMemberRole($industryId = null, $membershipId = null)
    {
        $userId       = (int) session('user_id');
        $industryId   = (int) $industryId;
        $membershipId = (int) $membershipId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        $role = (string) $this->request->getPost('role');
        if (! in_array($role, [IndustryUserModel::ROLE_ADMIN, IndustryUserModel::ROLE_SUPERVISOR], true)) {
            return $this->fail($industryId, 'Rol invalido.');
        }

        $permission = (string) $this->request->getPost('permission');
        if (! in_array($permission, [IndustryUserModel::PERM_READ, IndustryUserModel::PERM_EDIT], true)) {
            $permission = IndustryUserModel::PERM_READ;
        }

        $membershipModel = new IndustryUserModel();
        $membership      = $membershipModel->where('id', $membershipId)->where('industry_id', $industryId)->first();

        if ($membership === null) {
            return $this->fail($industryId, 'Ese acceso ya no existe.');
        }

        if ((int) $membership['user_id'] === $userId) {
            return $this->fail($industryId, 'No podés cambiar tu propio acceso.');
        }

        $wasAdmin = $membership['role'] === IndustryUserModel::ROLE_ADMIN;

        if ($wasAdmin && $role === IndustryUserModel::ROLE_SUPERVISOR && $this->adminCount($industryId) <= 1) {
            return $this->fail($industryId, 'Es el único admin del espacio. Nombrá otro admin antes de bajarlo a supervisor.');
        }

        $payload = ['role' => $role];
        if ($role === IndustryUserModel::ROLE_SUPERVISOR) {
            $payload['permission'] = $permission;
        }

        $membershipModel->update($membershipId, $payload);

        $message = $role === IndustryUserModel::ROLE_ADMIN
            ? 'Ahora es admin del espacio.'
            : 'Ahora es supervisor: ' . ($permission === IndustryUserModel::PERM_EDIT ? 'puede editar.' : 'solo lectura.');

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['members']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    private function adminCount(int $industryId): int
    {
        return (new IndustryUserModel())
            ->where('industry_id', $industryId)
            ->where('role', IndustryUserModel::ROLE_ADMIN)
            ->countAllResults();
    }

    /**
     * Cambia el permiso (read | edit) de un supervisor dentro del espacio.
     */
    public function updateMemberPermission($industryId = null, $membershipId = null)
    {
        $userId       = (int) session('user_id');
        $industryId   = (int) $industryId;
        $membershipId = (int) $membershipId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        $permission = (string) $this->request->getPost('permission');
        if (! in_array($permission, [IndustryUserModel::PERM_READ, IndustryUserModel::PERM_EDIT], true)) {
            return $this->fail($industryId, 'Permiso invalido.');
        }

        $membershipModel = new IndustryUserModel();
        $membership = $membershipModel->where('id', $membershipId)->where('industry_id', $industryId)->first();

        if ($membership === null) {
            return $this->fail($industryId, 'Ese acceso ya no existe.');
        }

        if ($membership['role'] === IndustryUserModel::ROLE_ADMIN) {
            return $this->fail($industryId, 'Un admin del espacio no tiene permiso que limitar.');
        }

        $membershipModel->update($membershipId, ['permission' => $permission]);

        $label   = $permission === IndustryUserModel::PERM_EDIT ? 'editar el espacio' : 'solo lectura';
        $message = 'Permiso actualizado: ahora puede ' . $label . '.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['members']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    public function updateMemberDeviceAccess($industryId = null, $membershipId = null, $deviceId = null)
    {
        $userId       = (int) session('user_id');
        $industryId   = (int) $industryId;
        $membershipId = (int) $membershipId;
        $deviceId     = (int) $deviceId;

        $membershipModel = new IndustryUserModel();
        if (! $membershipModel->canManageDeviceAccess($userId, $industryId)) {
            return $this->jsonError('No tenés permiso para administrar placas.', 403);
        }

        $target = $membershipModel->where('id', $membershipId)
            ->where('industry_id', $industryId)
            ->first();
        if ($target === null) {
            return $this->jsonError('Ese supervisor ya no existe.', 404);
        }

        if ((int) $target['user_id'] === $userId) {
            return $this->jsonError('No podés cambiar tus propias placas.', 403);
        }

        if ($target['role'] === IndustryUserModel::ROLE_ADMIN || ($target['permission'] ?? IndustryUserModel::PERM_READ) === IndustryUserModel::PERM_EDIT) {
            return $this->jsonError('Ese usuario ya ve todas las placas.', 422);
        }

        $device = (new Esp32DeviceModel())->where('id', $deviceId)
            ->where('industry_id', $industryId)
            ->first();
        if ($device === null) {
            return $this->jsonError('Esa placa no existe en este espacio.', 404);
        }

        $allowed = (string) $this->request->getPost('allowed') === '1';
        if (! (new DeviceAccessModel())->setAccess($membershipId, $deviceId, $allowed)) {
            return $this->jsonError('No se pudo guardar el permiso.');
        }

        return $this->response->setJSON([
            'ok'      => true,
            'csrf'    => csrf_hash(),
            'allowed' => $allowed,
        ]);
    }

    /**
     * Desbloquea sensores con la clave del espacio por 5 minutos.
     */
    public function unlockSensors($industryId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        $industry = (new IndustryModel())->find($industryId);
        $password = (string) $this->request->getPost('space_password');

        if ($industry === null || ! password_verify($password, (string) $industry['space_password_hash'])) {
            return $this->fail($industryId, 'Clave del espacio incorrecta.');
        }

        session()->set('space_unlock', [
            'id' => $industryId,
            'at' => time(),
        ]);

        $message = 'Espacio desbloqueado: sensores borrables por 5 minutos.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['sensors']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    public function requestPasswordReset($industryId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para cambiar la clave de ese espacio.');
        }

        if (! $this->validate(['user_password' => 'required'])) {
            return $this->fail($industryId, 'Escribí tu contraseña de usuario.');
        }

        $user = (new UserModel())->find($userId);
        if ($user === null || ! password_verify((string) $this->request->getPost('user_password'), (string) $user['password_hash'])) {
            return $this->fail($industryId, 'Tu contraseña de usuario es incorrecta.');
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            return $this->fail($industryId, 'Ese espacio ya no existe.');
        }

        $tokens = new SpaceActionTokenModel();
        $token  = $tokens->issue($industryId, $userId, SpaceActionTokenModel::PURPOSE_PASSWORD_RESET);
        if ($token === null || ! $this->sendSpacePasswordEmail($industry, $user, $token)) {
            if ($token !== null) {
                $tokens->where('token_hash', SpaceActionTokenModel::hashToken($token))->delete();
            }

            return $this->fail($industryId, 'No pudimos enviar el mail. Probá de nuevo.');
        }

        $message = 'Mail enviado. Abrí el enlace para crear una clave nueva.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    public function spacePasswordResetForm(?string $token = null)
    {
        $row = (new SpaceActionTokenModel())->findPending((string) $token, SpaceActionTokenModel::PURPOSE_PASSWORD_RESET);
        $industry = $row !== null ? (new IndustryModel())->find((int) $row['industry_id']) : null;

        if ($row === null || $industry === null) {
            return redirect()->to(base_url('login'))->with('error', 'El enlace no es válido o ya venció.');
        }

        return view('auth/space_password_reset', [
            'title'           => 'Cambiar clave del espacio | Preventive Control System',
            'hideAuthButtons' => true,
            'token'           => (string) $token,
            'spaceName'       => (string) $industry['space_name'],
        ]);
    }

    public function updateSpacePasswordByToken()
    {
        $rules = [
            'token'                  => 'required',
            'space_password'         => 'required|min_length[6]|max_length[200]',
            'space_password_confirm' => 'required|matches[space_password]',
        ];

        if (! $this->validate($rules, [
            'space_password_confirm' => ['matches' => 'Las claves no coinciden.'],
        ])) {
            $token = (string) $this->request->getPost('token');

            return redirect()->to($token !== '' ? base_url('espacios/clave/' . $token) : base_url('login'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $token = (string) $this->request->getPost('token');
        $tokens = new SpaceActionTokenModel();
        $row = $tokens->findPending($token, SpaceActionTokenModel::PURPOSE_PASSWORD_RESET);
        if ($row === null) {
            return redirect()->to(base_url('login'))->with('error', 'El enlace no es válido o ya venció.');
        }

        (new IndustryModel())->update((int) $row['industry_id'], [
            'space_password_hash' => (string) $this->request->getPost('space_password'),
        ]);
        $tokens->markUsed((int) $row['id']);

        return redirect()->to(base_url('registros-iot'))->with('success', 'Clave del espacio actualizada.');
    }

    public function requestDelete($industryId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para eliminar ese espacio.');
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            return $this->fail($industryId, 'Ese espacio ya no existe.');
        }

        $confirmName = trim((string) $this->request->getPost('confirm_name'));
        if ($confirmName !== (string) $industry['space_name']) {
            return $this->fail($industryId, 'El nombre no coincide. No se eliminó nada.');
        }

        $password = (string) $this->request->getPost('space_password');
        if (! password_verify($password, (string) $industry['space_password_hash'])) {
            return $this->fail($industryId, 'Contraseña incorrecta. No se eliminó nada.');
        }

        $user = (new UserModel())->find($userId);
        if ($user === null) {
            return $this->fail($industryId, 'No encontramos tu usuario.');
        }

        $tokens = new SpaceActionTokenModel();
        $token  = $tokens->issue($industryId, $userId, SpaceActionTokenModel::PURPOSE_DELETE);
        if ($token === null || ! $this->sendSpaceDeleteEmail($industry, $user, $token)) {
            if ($token !== null) {
                $tokens->where('token_hash', SpaceActionTokenModel::hashToken($token))->delete();
            }

            return $this->fail($industryId, 'No pudimos enviar el mail de verificación. Probá de nuevo.');
        }

        $message = 'Te enviamos un mail para confirmar la eliminación.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    public function deleteConfirmForm(?string $token = null)
    {
        $row = (new SpaceActionTokenModel())->findPending((string) $token, SpaceActionTokenModel::PURPOSE_DELETE);
        $industry = $row !== null ? (new IndustryModel())->find((int) $row['industry_id']) : null;

        if ($row === null || $industry === null) {
            return redirect()->to(base_url('login'))->with('error', 'El enlace no es válido o ya venció.');
        }

        return view('auth/space_delete_confirm', [
            'title'           => 'Eliminar espacio | Preventive Control System',
            'hideAuthButtons' => true,
            'token'           => (string) $token,
            'spaceName'       => (string) $industry['space_name'],
        ]);
    }

    public function deleteByToken(?string $token = null)
    {
        $tokens = new SpaceActionTokenModel();
        $row = $tokens->findPending((string) $token, SpaceActionTokenModel::PURPOSE_DELETE);
        $industry = $row !== null ? (new IndustryModel())->find((int) $row['industry_id']) : null;

        if ($row === null || $industry === null) {
            return redirect()->to(base_url('login'))->with('error', 'El enlace no es válido o ya venció.');
        }

        $industryId = (int) $industry['id'];
        $db = \Config\Database::connect();
        $db->transStart();
        $tokens->markUsed((int) $row['id']);
        (new IndustryModel())->delete($industryId);
        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to(base_url('espacios/eliminar/' . $token))
                ->with('error', 'No pudimos eliminar el espacio. Probá de nuevo.');
        }

        if ((int) session('active_industry_id') === $industryId) {
            session()->remove(['active_industry_id', 'active_industry_role']);
        }

        return view('auth/space_deleted', [
            'title'           => 'Espacio eliminado | Preventive Control System',
            'hideAuthButtons' => true,
            'industryId'      => $industryId,
        ]);
    }

    /* ── Invitaciones por correo ──────────────────────────────────────
       Mismo flujo que compartir un documento: el admin escribe un mail y
       elige el acceso, el invitado recibe un link de un solo uso y recién
       cuando lo abre —con la sesión de ESE mail— se crea la membresía.
       ─────────────────────────────────────────────────────────────────── */

    public function invite($industryId = null)
    {
        $userId     = (int) session('user_id');
        $industryId = (int) $industryId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        if (! $this->validate(['email' => 'required|valid_email|max_length[254]'])) {
            return $this->fail($industryId, 'Escribí un correo válido para invitar.');
        }

        $email = mb_strtolower(trim((string) $this->request->getPost('email')));

        $role = (string) $this->request->getPost('role');
        if (! in_array($role, [IndustryUserModel::ROLE_ADMIN, IndustryUserModel::ROLE_SUPERVISOR], true)) {
            $role = IndustryUserModel::ROLE_SUPERVISOR;
        }

        $permission = (string) $this->request->getPost('permission');
        if (! in_array($permission, [IndustryUserModel::PERM_READ, IndustryUserModel::PERM_EDIT], true)) {
            $permission = IndustryUserModel::PERM_READ;
        }

        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null) {
            return $this->fail($industryId, 'Ese espacio ya no existe.');
        }

        // Si el mail ya es de alguien del espacio no hace falta invitarlo.
        $existing = (new UserModel())->where('email', $email)->first();
        if ($existing !== null && (new IndustryUserModel())->userBelongsTo((int) $existing['id'], $industryId)) {
            return $this->fail($industryId, 'Esa persona ya tiene acceso al espacio.');
        }

        $invitationModel = new SpaceInvitationModel();
        $token           = $invitationModel->issue($industryId, $email, $role, $permission, $userId);

        if ($token === null) {
            return $this->fail($industryId, 'No pudimos generar la invitación. Probá de nuevo.');
        }

        if (! $this->sendInvitationEmail($industry, $email, $role, $permission, $token)) {
            // Sin correo no hay invitación posible: se borra para no dejar un
            // pendiente fantasma en la lista del modal.
            $invitationModel->where('token_hash', SpaceInvitationModel::hashToken($token))->delete();

            return $this->fail($industryId, 'No pudimos enviar la invitación a ' . $email . '. Revisá el correo y probá de nuevo.');
        }

        $message = 'Invitación enviada a ' . $email . '.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['members']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    public function cancelInvitation($industryId = null, $invitationId = null)
    {
        $userId       = (int) session('user_id');
        $industryId   = (int) $industryId;
        $invitationId = (int) $invitationId;

        if (! $this->isAdmin($userId, $industryId)) {
            return $this->fail($industryId, 'No tenés permiso para administrar ese espacio.');
        }

        $invitationModel = new SpaceInvitationModel();
        $invitation      = $invitationModel->where('id', $invitationId)->where('industry_id', $industryId)->first();

        if ($invitation === null) {
            return $this->fail($industryId, 'Esa invitación ya no existe.');
        }

        $invitationModel->delete($invitationId);

        $message = 'Invitación cancelada: el enlace dejó de servir.';

        if ($this->wantsJson()) {
            return $this->jsonOk($industryId, $message, ['members']);
        }

        return redirect()->to(base_url('registros-iot?config=' . $industryId))->with('success', $message);
    }

    /**
     * Link del correo. Si no hay sesión, se guarda el token y se manda a
     * ingresar: al terminar el login, Auth vuelve solo a esta URL.
     */
    public function acceptInvitation(?string $token = null)
    {
        $token           = (string) $token;
        $invitationModel = new SpaceInvitationModel();
        $invitation      = $invitationModel->findPending($token);

        if ($invitation === null) {
            session()->remove('pending_invitation');

            return redirect()->to(base_url('registros-iot'))
                ->with('error', 'Esa invitación no existe, ya fue usada o venció. Pedile al admin del espacio que te invite de nuevo.');
        }

        $userId = (int) session('user_id');
        if ($userId <= 0) {
            session()->set('pending_invitation', $token);

            return redirect()->to(base_url('reg-log'))
                ->with('error', 'Entrá con la cuenta de ' . $invitation['email'] . ' para aceptar la invitación. Si todavía no tenés cuenta, creala con ese correo.');
        }

        $user = (new UserModel())->find($userId);
        if ($user === null || mb_strtolower(trim((string) $user['email'])) !== $invitation['email']) {
            session()->remove('pending_invitation');

            return redirect()->to(base_url('registros-iot'))
                ->with('error', 'La invitación es para ' . $invitation['email'] . '. Cerrá sesión y entrá con esa cuenta.');
        }

        session()->remove('pending_invitation');

        $industryId      = (int) $invitation['industry_id'];
        $membershipModel = new IndustryUserModel();

        if ($membershipModel->userBelongsTo($userId, $industryId)) {
            $invitationModel->markAccepted((int) $invitation['id'], $userId);

            return redirect()->to(base_url('registros-iot'))->with('success', 'Ya tenías acceso a ese espacio.');
        }

        $membershipModel->insert([
            'user_id'     => $userId,
            'industry_id' => $industryId,
            'role'        => $invitation['role'],
            'permission'  => $invitation['permission'],
        ]);

        $invitationModel->markAccepted((int) $invitation['id'], $userId);

        session()->set([
            'active_industry_id'   => $industryId,
            'active_industry_role' => $invitation['role'],
        ]);

        $industry = (new IndustryModel())->find($industryId);
        $name     = $industry['space_name'] ?? 'el espacio';

        return redirect()->to(base_url('registros-iot'))
            ->with('success', 'Listo: ya tenés acceso a ' . $name . '.');
    }

    private function sendInvitationEmail(array $industry, string $email, string $role, string $permission, string $token): bool
    {
        $config    = config('Email');
        $fromEmail = $config->fromEmail !== '' ? $config->fromEmail : $config->SMTPUser;
        $fromName  = $config->fromName !== '' ? $config->fromName : 'Preventive Control System';

        if ($fromEmail === '') {
            log_message('error', 'Space invitation could not be sent: fromEmail/SMTPUser is empty.');

            return false;
        }

        $inviterName = trim((string) session('user_name'));

        $body = view('emails/space_invitation', [
            'spaceName'       => (string) $industry['space_name'],
            'companyName'     => (string) $industry['company_name'],
            'sector'          => (string) ($industry['sector'] ?? ''),
            'inviterName'     => $inviterName !== '' ? $inviterName : 'Un admin del espacio',
            'roleLabel'       => $role === IndustryUserModel::ROLE_ADMIN ? 'Admin del espacio' : 'Supervisor',
            'permissionLabel' => $role === IndustryUserModel::ROLE_ADMIN
                ? 'Control total'
                : ($permission === IndustryUserModel::PERM_EDIT ? 'Puede editar' : 'Solo lectura'),
            'email'           => $email,
            'acceptLink'      => base_url('invitacion/' . $token),
            'expiresLabel'    => date('d/m/Y', time() + SpaceInvitationModel::TTL_SECONDS),
        ]);

        $mailer = \Config\Services::email();
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->setTo($email);
        $mailer->setSubject('Te invitaron a ' . $industry['space_name'] . ' en Preventive Control System');
        $mailer->setMailType('html');
        $mailer->setMessage($body);

        if (! $mailer->send()) {
            log_message('error', 'Space invitation email failed for {email}: {debug}', [
                'email' => $email,
                'debug' => $mailer->printDebugger(['headers', 'subject']),
            ]);

            return false;
        }

        return true;
    }

    private function sendSpacePasswordEmail(array $industry, array $user, string $token): bool
    {
        return $this->sendSpaceActionEmail(
            (string) $user['email'],
            'Cambiar clave de ' . $industry['space_name'],
            view('emails/space_password_reset', [
                'spaceName' => (string) $industry['space_name'],
                'resetLink' => base_url('espacios/clave/' . $token),
            ])
        );
    }

    private function sendSpaceDeleteEmail(array $industry, array $user, string $token): bool
    {
        return $this->sendSpaceActionEmail(
            (string) $user['email'],
            'Confirmar eliminación de ' . $industry['space_name'],
            view('emails/space_delete_confirmation', [
                'spaceName'  => (string) $industry['space_name'],
                'deleteLink' => base_url('espacios/eliminar/' . $token),
            ])
        );
    }

    private function sendSpaceActionEmail(string $to, string $subject, string $body): bool
    {
        $config    = config('Email');
        $fromEmail = $config->fromEmail !== '' ? $config->fromEmail : $config->SMTPUser;
        $fromName  = $config->fromName !== '' ? $config->fromName : 'Preventive Control System';

        if ($fromEmail === '') {
            log_message('error', 'Space action email could not be sent: fromEmail/SMTPUser is empty.');

            return false;
        }

        $mailer = \Config\Services::email();
        $mailer->setFrom($fromEmail, $fromName);
        $mailer->setTo($to);
        $mailer->setSubject($subject);
        $mailer->setMailType('html');
        $mailer->setMessage($body);

        if (! $mailer->send()) {
            log_message('error', 'Space action email failed for {email}: {debug}', [
                'email' => $to,
                'debug' => $mailer->printDebugger(['headers', 'subject']),
            ]);

            return false;
        }

        return true;
    }

    private function isAdmin(int $userId, int $industryId): bool
    {
        return $userId > 0
            && $industryId > 0
            && (new IndustryUserModel())->roleFor($userId, $industryId) === IndustryUserModel::ROLE_ADMIN;
    }
}
