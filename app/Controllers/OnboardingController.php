<?php

namespace App\Controllers;

use App\Models\IndustryMetricModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SensorTypeModel;
use App\Models\StockTableModel;
use Config\Database;

class OnboardingController extends BaseController
{
    /**
     * Paso intermedio entre el registro y el onboarding.
     * Por ahora es una pantalla simple de "cuenta verificada"; aqui se
     * engancharia la verificación por email real mas adelante.
     */
    public function verify()
    {
        return view('onboarding/verificar', [
            'title'           => 'Verificación | Preventive Control System',
            'hideAuthButtons' => true,
            'userName'        => session('user_name') ?? 'Usuario',
        ]);
    }

    /**
     * Vista del onboarding de creacion de espacio.
     */
    public function create()
    {
        return view('onboarding/crear-espacio', [
            'title'           => 'Crear espacio | Preventive Control System',
            'hideAuthButtons' => true,
            'userName'        => session('user_name') ?? 'Usuario',
            'sensorTypes'     => (new SensorTypeModel())->orderBy('name', 'ASC')->findAll(),
            'errors'          => session('errors') ?? [],
        ]);
    }

    /**
     * Procesa el alta de la industria + membresía admin + métricas elegidas.
     */
    public function store()
    {
        $userId = (int) session('user_id');
        if ($userId <= 0) {
            return redirect()->to(base_url('reg-log'));
        }

        $rules = [
            'company_name'           => 'required|min_length[2]|max_length[200]',
            'space_name'             => 'required|min_length[2]|max_length[200]',
            'sector'                 => 'permit_empty|max_length[120]',
            'space_password'         => 'required|min_length[6]|max_length[200]',
            'space_password_confirm' => 'required|matches[space_password]',
        ];

        $messages = [
            'space_password_confirm' => [
                'matches' => 'Las contraseñas del espacio no coinciden.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to(base_url('crear-espacio'))
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Métricas elegidas (codes de sensor_types). Al menos una.
        $selectedCodes = (array) ($this->request->getPost('metrics') ?? []);
        $sensorTypeModel = new SensorTypeModel();
        $validTypes = [];
        foreach ($selectedCodes as $code) {
            $type = $sensorTypeModel->findByCode((string) $code);
            if ($type !== null) {
                $validTypes[] = $type;
            }
        }

        if ($validTypes === []) {
            return redirect()->to(base_url('crear-espacio'))
                ->withInput()
                ->with('errors', ['metrics' => 'Elegí al menos una metrica para monitorear.']);
        }

        $industryModel = new IndustryModel();
        $company = trim((string) $this->request->getPost('company_name'));
        $sector  = trim((string) $this->request->getPost('sector'));
        $uid     = $industryModel->generateUniqueUid($company, $sector);

        $db = Database::connect();
        $db->transStart();

        $industryId = $industryModel->insert([
            'industry_uid'        => $uid,
            'company_name'        => $company,
            'space_name'          => trim((string) $this->request->getPost('space_name')),
            'sector'              => $sector !== '' ? $sector : null,
            'space_password_hash' => (string) $this->request->getPost('space_password'), // el modelo la hashea
            'created_by'          => $userId,
        ], true);

        // Creador = admin_principal
        (new IndustryUserModel())->insert([
            'industry_id' => $industryId,
            'user_id'     => $userId,
            'role'        => IndustryUserModel::ROLE_ADMIN,
        ]);

        // Métricas + gráfico sugerido
        $metricModel = new IndustryMetricModel();
        foreach ($validTypes as $type) {
            $metricModel->insert([
                'industry_id'     => $industryId,
                'sensor_type_id'  => $type['id'],
                'suggested_chart' => $type['default_chart'] ?? 'line',
            ]);
        }

        // Todo espacio nace con su tabla de stock: el mínimo es una, y así el
        // módulo nunca arranca en un estado sin forma.
        (new StockTableModel())->provisionDefault((int) $industryId, $company, $userId);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Onboarding store failed for user {id}', ['id' => $userId]);

            return redirect()->to(base_url('crear-espacio'))
                ->withInput()
                ->with('error', 'No se pudo crear el espacio. Intentá nuevamente.');
        }

        session()->set([
            'active_industry_id'   => (int) $industryId,
            'active_industry_role' => IndustryUserModel::ROLE_ADMIN,
        ]);

        return redirect()->to(base_url('esp32/agregar?from=onboarding&industry_id=' . $industryId))
            ->with('success', "Espacio creado. ID: {$uid}. Ahora vincula tu primera ESP32.")
            ->with('created_industry_uid', $uid);
    }
}
