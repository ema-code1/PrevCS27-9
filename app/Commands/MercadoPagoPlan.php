<?php

namespace App\Commands;

use App\Libraries\MercadoPagoSubscriptions;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Subscription;
use MercadoPago\Exceptions\MPApiException;
use Throwable;

/**
 * spark mp:plan
 *
 * Crea o actualiza en Mercado Pago el plan de suscripción de PrevCS Pro con
 * los valores del .env (MP_PLAN_AMOUNT, MP_PLAN_CURRENCY, APP_URL). Es una
 * acción de operador: no hay ninguna ruta web que cree o cambie planes.
 *
 * Uso:
 *   php spark mp:plan
 *
 * - Sin MP_SUBSCRIPTION_PLAN_ID: crea el plan e imprime la línea para el .env.
 * - Con MP_SUBSCRIPTION_PLAN_ID: muestra el plan actual y, si el precio del
 *   .env es otro, lo actualiza (pide confirmación).
 */
class MercadoPagoPlan extends BaseCommand
{
    protected $group       = 'PrevCS';
    protected $name        = 'mp:plan';
    protected $description = 'Crea o actualiza en Mercado Pago el plan de suscripción de PrevCS Pro con los valores del .env.';

    protected $usage   = 'mp:plan';
    protected $options = [];

    public function run(array $params)
    {
        $config = config(Subscription::class);
        if ($config->accessToken === '') {
            CLI::error('Falta MP_ACCESS_TOKEN en el .env (Developer Dashboard → tu app → Credenciales → Prueba).');

            return 1;
        }

        $offer = $config->trustedOffer();
        $mp    = new MercadoPagoSubscriptions($config);

        CLI::write('Plan según el .env:', 'yellow');
        CLI::table([
            ['Nombre',     $offer['reason']],
            ['Precio',     $offer['currency'] . ' ' . $config->displayAmount()],
            ['Frecuencia', $offer['frequency'] . ' ' . $offer['frequencyType']],
            ['back_url',   $offer['backUrl']],
        ], ['Campo', 'Valor']);

        try {
            if ($config->planId === '') {
                if (CLI::prompt('No hay MP_SUBSCRIPTION_PLAN_ID. ¿Crear el plan en Mercado Pago?', ['s', 'n']) !== 's') {
                    return 0;
                }

                $plan = $mp->createPlan($offer);

                CLI::newLine();
                CLI::write('✔ Plan creado (' . $plan['status'] . ').', 'green');
                CLI::write('Pegá esta línea en tu .env:', 'yellow');
                CLI::write('MP_SUBSCRIPTION_PLAN_ID=' . $plan['id'], 'cyan');

                return 0;
            }

            $current = $mp->findPlan($config->planId);

            CLI::newLine();
            CLI::write('Plan en Mercado Pago (' . $config->planId . '):', 'yellow');
            CLI::table([
                ['Estado', $current['status']],
                ['Nombre', $current['reason']],
                ['Precio', $current['currency'] . ' ' . ($current['amount'] ?? '—')],
            ], ['Campo', 'Valor']);

            $samePrice = $current['amount'] !== null
                && abs($current['amount'] - $offer['amount']) < 0.005
                && $current['currency'] === $offer['currency'];

            if ($samePrice) {
                CLI::write('✔ El plan ya coincide con el .env. No hay nada que actualizar.', 'green');

                return 0;
            }

            if (CLI::prompt('¿Actualizar el plan en Mercado Pago con el precio del .env?', ['s', 'n']) !== 's') {
                return 0;
            }

            $updated = $mp->updatePlan($config->planId, $offer);
            CLI::write('✔ Plan actualizado: ' . $updated['currency'] . ' ' . ($updated['amount'] ?? '—'), 'green');

            return 0;
        } catch (MPApiException $e) {
            $content = $e->getApiResponse()->getContent();
            CLI::error('Mercado Pago respondió ' . $e->getStatusCode() . ': ' . (string) ($content['message'] ?? $e->getMessage()));

            return 1;
        } catch (Throwable $e) {
            CLI::error('No se pudo conectar con Mercado Pago: ' . $e->getMessage());

            return 1;
        }
    }
}
