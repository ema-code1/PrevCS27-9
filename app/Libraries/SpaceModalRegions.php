<?php

namespace App\Libraries;

use App\Models\Esp32DeviceModel;
use App\Models\Esp32SensorModel;
use App\Models\IndustryModel;
use App\Models\IndustryUserModel;
use App\Models\SpaceInvitationModel;

/**
 * Regiones del modal "Configurar espacio", dibujadas de nuevo desde el
 * servidor.
 *
 * Cada acción del modal viaja por fetch y contesta con el HTML actualizado de
 * la parte que cambió, para que el navegador la reemplace en su lugar en vez
 * de recargar la página entera (que cerraba la ventana, perdía el scroll y lo
 * que estuviera a medio escribir).
 *
 * Vive acá y no en un controlador porque el borrado de sensores lo hace
 * SensorController y el resto IndustryController: las dos rutas tienen que
 * devolver exactamente el mismo marcado que sirvió la primera carga.
 */
class SpaceModalRegions
{
    public const MEMBERS = 'members';
    public const TOKENS  = 'tokens';

    /** Cuánto dura el desbloqueo con la clave del espacio. */
    private const UNLOCK_SECONDS = 300;

    /**
     * @param list<string> $regions
     *
     * @return array<string, string> Mapa data-region => HTML.
     */
    public function render(int $industryId, array $regions): array
    {
        $industry = (new IndustryModel())->find($industryId);
        if ($industry === null || $regions === []) {
            return [];
        }

        $out = [];

        foreach ($regions as $region) {
            if ($region === self::MEMBERS) {
                $out['members-' . $industryId] = $this->members($industry, $industryId);
            }

            if ($region === self::TOKENS) {
                $out['tokens-' . $industryId] = $this->tokens($industry, $industryId);
            }
        }

        return $out;
    }

    private function members(array $industry, int $industryId): string
    {
        return view('registros-iot/_space_members', [
            'ind' => $industry + [
                'members'     => (new IndustryUserModel())->membersForIndustry($industryId),
                'invitations' => (new SpaceInvitationModel())->pendingForIndustry($industryId),
            ],
            'sessionUserId' => (int) session('user_id'),
        ]);
    }

    private function tokens(array $industry, int $industryId): string
    {
        $unlock = session('space_unlock');
        $fresh  = is_array($unlock) && (time() - (int) ($unlock['at'] ?? 0)) <= self::UNLOCK_SECONDS;

        return view('registros-iot/_space_tokens', [
            'ind' => $industry + [
                'devices' => (new Esp32DeviceModel())->devicesForIndustry($industryId),
                'sensors' => (new Esp32SensorModel())->sensorsForIndustry($industryId),
            ],
            'isUnlocked'     => $fresh && (int) ($unlock['id'] ?? 0) === $industryId,
            'unlockedTokens' => $fresh && is_array($unlock['tokens'] ?? null) ? $unlock['tokens'] : [],
        ]);
    }
}
