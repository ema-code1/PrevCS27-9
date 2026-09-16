<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\DotEnv;
use Config\Database;
use Config\Subscription;
use Throwable;

/**
 * spark prevcs:setup
 *
 * Deja el proyecto funcionando en una PC nueva (o actualiza una existente).
 * Se puede correr todas las veces que haga falta: cada paso es idempotente.
 *
 *   1. Revisa PHP, extensiones y dependencias de Composer (vendor/).
 *   2. Crea el .env desde .env.example si no existe.
 *   3. Revisa que las carpetas de writable/ tengan permiso de escritura.
 *   4. Crea la base de datos si no existe y corre las migraciones pendientes.
 *   5. Muestra qué falta configurar (sin imprimir valores del .env).
 *
 * Lo más simple es no llamarlo directo sino con setup.cmd, que antes corre
 * `composer install`.
 */
class Setup extends BaseCommand
{
    protected $group       = 'PrevCS';
    protected $name        = 'prevcs:setup';
    protected $description = 'Instala o actualiza PrevCS en esta PC: .env, base de datos y migraciones.';

    protected $usage   = 'prevcs:setup';
    protected $options = [];

    private const EXTENSIONS = ['intl', 'mbstring', 'mysqli', 'curl', 'openssl', 'json'];

    private const WRITABLE_DIRS = ['cache', 'logs', 'session', 'uploads'];

    public function run(array $params)
    {
        CLI::write('PrevCS · instalación', 'green');
        CLI::newLine();

        if (! $this->checkPhp() || ! $this->checkVendor()) {
            return 1;
        }

        $this->ensureEnvFile();
        $this->checkWritable();

        if (! $this->ensureDatabase()) {
            return 1;
        }

        CLI::write('Migraciones', 'yellow');
        $this->call('migrate', ['all' => null]);
        CLI::newLine();

        $this->summary();

        return 0;
    }

    private function checkPhp(): bool
    {
        $missing = array_values(array_filter(self::EXTENSIONS, static fn (string $ext): bool => ! extension_loaded($ext)));

        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            CLI::error('Se necesita PHP 8.2 o superior (tenés ' . PHP_VERSION . '). Actualizá XAMPP.');

            return false;
        }
        if ($missing !== []) {
            CLI::error('Faltan extensiones de PHP: ' . implode(', ', $missing) . '.');
            CLI::write('Habilitalas en C:\xampp\php\php.ini (quitá el ";" de extension=...) y reiniciá Apache.');

            return false;
        }

        CLI::write('✔ PHP ' . PHP_VERSION . ' con ' . implode(', ', self::EXTENSIONS), 'green');

        return true;
    }

    private function checkVendor(): bool
    {
        if (! is_file(ROOTPATH . 'vendor/mercadopago/dx-php/composer.json')) {
            CLI::error('Faltan las dependencias de Composer (vendor/).');
            CLI::write('Corré setup.cmd, o en esta carpeta: composer install --no-dev');

            return false;
        }

        CLI::write('✔ Dependencias de Composer instaladas', 'green');

        return true;
    }

    private function ensureEnvFile(): void
    {
        if (is_file(ROOTPATH . '.env')) {
            CLI::write('✔ .env encontrado', 'green');

            return;
        }

        copy(ROOTPATH . '.env.example', ROOTPATH . '.env');
        // El .env se lee al arrancar spark: se carga ahora para usarlo en los pasos siguientes.
        (new DotEnv(ROOTPATH))->load();

        CLI::write('✔ .env creado desde .env.example (valores para XAMPP)', 'green');
    }

    private function checkWritable(): void
    {
        $blocked = [];
        foreach (self::WRITABLE_DIRS as $dir) {
            $path = WRITEPATH . $dir;
            if (! is_dir($path)) {
                mkdir($path, 0775, true);
            }
            if (! is_writable($path)) {
                $blocked[] = 'writable/' . $dir;
            }
        }

        if ($blocked !== []) {
            CLI::write('⚠ Sin permiso de escritura: ' . implode(', ', $blocked), 'yellow');

            return;
        }

        CLI::write('✔ Carpetas de writable/ con permiso de escritura', 'green');
    }

    /** Crea la base configurada en el .env si todavía no existe. */
    private function ensureDatabase(): bool
    {
        $group = (new Database())->default;
        $name  = (string) $group['database'];

        try {
            // Conexión sin base seleccionada: la base puede no existir todavía.
            $server = Database::connect(['database' => ''] + $group, false);
            $server->initialize();
            Database::forge($server)->createDatabase($name, true);
            $server->close();
        } catch (Throwable $e) {
            CLI::error('No se pudo conectar a MySQL en ' . $group['hostname'] . ' con el usuario ' . $group['username'] . '.');
            CLI::write('Revisá que MySQL esté prendido en el panel de XAMPP y los datos database.default.* del .env.');
            CLI::write('Detalle: ' . $e->getMessage());

            return false;
        }

        CLI::write('✔ Base de datos "' . $name . '" lista', 'green');
        CLI::newLine();

        return true;
    }

    private function summary(): void
    {
        $mp = config(Subscription::class);

        CLI::write('Mercado Pago (.env)', 'yellow');
        CLI::table([
            ['MP_ACCESS_TOKEN',         $mp->accessToken !== '' ? 'cargado' : 'vacío'],
            ['MP_PUBLIC_KEY',           $mp->publicKey !== '' ? 'cargado' : 'vacío'],
            ['MP_SUBSCRIPTION_PLAN_ID', $mp->planId !== '' ? 'cargado' : 'vacío'],
            ['MP_WEBHOOK_SECRET',       $mp->webhookSecret !== '' ? 'cargado' : 'vacío'],
        ], ['Variable', 'Estado']);

        $steps = [];
        if ($mp->accessToken === '' || $mp->publicKey === '') {
            $steps[] = 'Cargá MP_ACCESS_TOKEN y MP_PUBLIC_KEY en el .env (panel de Mercado Pago → Credenciales → Prueba).';
        } elseif ($mp->planId === '') {
            $steps[] = 'Corré `php spark mp:plan` y pegá el MP_SUBSCRIPTION_PLAN_ID en el .env.';
        }
        if ($mp->webhookSecret === '') {
            $steps[] = 'Para webhooks: panel de Mercado Pago → Webhooks → clave secreta en MP_WEBHOOK_SECRET.';
        }
        if (env('email.SMTPUser', '') === '') {
            $steps[] = 'Para mandar correos (verificación, invitaciones): completá email.SMTPUser y email.SMTPPass en el .env.';
        }

        CLI::newLine();
        CLI::write('✔ PrevCS listo: ' . rtrim((string) config('App')->baseURL, '/') . '/', 'green');

        if ($steps !== []) {
            CLI::newLine();
            CLI::write('Pendiente (opcional según lo que vayas a usar):', 'yellow');
            foreach ($steps as $step) {
                CLI::write('  • ' . $step);
            }
        }
    }
}
