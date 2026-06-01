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
}
