<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Landing::index');
$routes->get('portfolio', 'Portfolio::index');
$routes->get('portfolio.php', 'Portfolio::index');

// Form answers — metrics es publico (consumido por la vista de Portfolio);
// import esta protegido por AuthGuard global (solo usuarios logueados).
$routes->get('api/form-metrics', 'FormAnswers::metrics');
$routes->get('form-answers/import', 'FormAnswers::import');

$routes->get('principal', 'Home::index');
$routes->get('registros-iot', 'Home::registrosIot');
$routes->get('admin-account', 'AdminAccount::index');
$routes->post('admin-account', 'AdminAccount::update');
$routes->get('api/iot-readings', 'IotReadings::index');
$routes->get('login', 'Auth::index');
$routes->get('reg-log', 'Auth::index');
$routes->get('register', 'Auth::index');
$routes->post('login', 'Auth::login');
$routes->post('register', 'Auth::register');
$routes->post('logout', 'Auth::logout');
$routes->get('logout', 'Auth::logout');
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendResetLink');
$routes->get('reset-password/(:segment)', 'Auth::resetPassword/$1');
$routes->post('reset-password', 'Auth::updatePassword');
$routes->get('admin-account', 'Account::index');
$routes->post('admin-account/update', 'Account::update');

// === Modulo IoT multi-industria ===
// Onboarding post-registro: registro -> verificacion -> crear-espacio
$routes->get('verificar-cuenta', 'OnboardingController::verify');
$routes->get('crear-espacio', 'OnboardingController::create');
$routes->post('crear-espacio', 'OnboardingController::store');

// Unirse a una industria existente (como supervisor)
$routes->get('unirse-espacio', 'IndustryController::joinForm');
$routes->post('unirse-espacio', 'IndustryController::join');

// ESP32 (rutas especificas antes de la parametrica)
$routes->get('esp32', 'Esp32Controller::index');
$routes->get('esp32/agregar', 'Esp32Controller::createForm');
$routes->post('esp32/agregar', 'Esp32Controller::store');
$routes->post('esp32/(:num)/sensores', 'SensorController::store/$1');
$routes->get('esp32/(:num)', 'Esp32Controller::show/$1');

// APIs IoT
$routes->get('api/esp32/(:num)/lecturas', 'Esp32Controller::readings/$1');
$routes->post('api/iot/lectura', 'IoTController::ingest'); // ingesta desde ESP32 (MAC + token)
