<?php

namespace App\Controllers;

use App\Models\IndustryModel;
use App\Models\IndustryUserModel;

class IndustryController extends BaseController
{
    private const JOIN_ERROR = 'El ID o la contrasena del espacio no son correctos.';

    /**
     * Formulario para unirse a una industria existente.
     */
    public function joinForm()
    {
        return view('onboarding/unirse-espacio', [
            'title'           => 'Unirse a un espacio | Prevention Control System',
            'hideAuthButtons' => true,
            'errors'          => session('errors') ?? [],
        ]);
    }

    /**
     * Valida ID + contrasena del espacio y agrega al usuario como supervisor.
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

        $industryModel = new IndustryModel();
        $industry      = $industryModel->findByUid($uid);

        // Mensaje generico: no revelamos si el UID existe o si fallo el password.
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
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenes permiso para editar ese espacio.');
        }

        $rules = [
            'company_name'   => 'required|min_length[2]|max_length[200]',
            'space_name'     => 'required|min_length[2]|max_length[200]',
            'sector'         => 'permit_empty|max_length[120]',
            'space_password' => 'permit_empty|min_length[6]|max_length[200]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->to(base_url('registros-iot'))
                ->withInput()
                ->with('errors', $this->validator->getErrors())
                ->with('editing_industry_id', $industryId);
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

        return redirect()->to(base_url('registros-iot'))->with('success', 'Espacio actualizado.');
    }

    public function removeMember($industryId = null, $membershipId = null)
    {
        $userId = (int) session('user_id');
        $industryId = (int) $industryId;
        $membershipId = (int) $membershipId;

        if (! $this->isAdmin($userId, $industryId)) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No tenes permiso para administrar ese espacio.');
        }

        $membershipModel = new IndustryUserModel();
        $membership = $membershipModel->where('id', $membershipId)->where('industry_id', $industryId)->first();

        if ($membership === null) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'Ese acceso ya no existe.');
        }

        if ((int) $membership['user_id'] === $userId || $membership['role'] === IndustryUserModel::ROLE_ADMIN) {
            return redirect()->to(base_url('registros-iot'))->with('error', 'No se puede quitar al administrador principal desde aca.');
        }

        $membershipModel->delete($membershipId);

        return redirect()->to(base_url('registros-iot'))->with('success', 'Acceso eliminado.');
    }

    private function isAdmin(int $userId, int $industryId): bool
    {
        return $userId > 0
            && $industryId > 0
            && (new IndustryUserModel())->roleFor($userId, $industryId) === IndustryUserModel::ROLE_ADMIN;
    }
}
