<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Landing::index');
$routes->get('pago', 'Pago::index');

// === Suscripción PrevCS Pro (Mercado Pago · Suscripciones con plan) ===
// Public Key para MercadoPago.js (JSON sin cache).
$routes->get('api/mp-config', 'SubscriptionController::config');
// Crear la suscripción: publica, porque el pago ocurre antes del registro.
$routes->post('api/subscriptions', 'SubscriptionController::create');
// GET /api/subscriptions/:id → estado actual (solo el dueño, logueado).
$routes->get('api/subscriptions/(:segment)', 'SubscriptionController::show/$1');
// Pausar / reactivar / cancelar: acciones cerradas, nunca un estado libre.
$routes->post('api/subscriptions/(:segment)/(pause|reactivate|cancel)', 'SubscriptionController::lifecycle/$1/$2');
$routes->get('portfolio', 'Portfolio::index');
$routes->get('portfolio.php', 'Portfolio::index');

// Form answers — metrics es publico (consumido por la vista de Portfolio);
// import esta protegido por AuthGuard global (solo usuarios logueados).
$routes->get('api/form-metrics', 'FormAnswers::metrics');
$routes->get('form-answers/import', 'FormAnswers::import');

$routes->get('principal', 'Home::index');
$routes->get('admin-account', 'AdminAccount::index');
$routes->post('admin-account', 'AdminAccount::update');
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
// Registros IoT por-espacio (reemplaza la vieja vista global con datos demo)
$routes->get('registros-iot', 'RegistrosController::index');
$routes->get('registros-iot/(:num)', 'RegistrosController::space/$1');
$routes->get('api/espacio/(:num)/lecturas', 'RegistrosController::readings/$1');

// Verificacion de correo y 2FA
$routes->get('verificar-cuenta', 'Auth::verificationForm');
$routes->post('verificar-cuenta', 'Auth::verifyAccountCode');
$routes->post('verificar-cuenta/reenviar', 'Auth::resendAccountCode');
$routes->get('verificar-login', 'Auth::twoFactorForm');
$routes->post('verificar-login', 'Auth::verifyTwoFactorCode');
$routes->post('verificar-login/reenviar', 'Auth::resendTwoFactorCode');
$routes->get('cuenta-creada', 'Auth::accountCreated');

// Onboarding post-registro: verificacion -> crear-espacio
$routes->get('crear-espacio', 'OnboardingController::create');
$routes->post('crear-espacio', 'OnboardingController::store');

// Unirse a una industria existente (como supervisor)
$routes->get('unirse-espacio', 'IndustryController::joinForm');
$routes->post('unirse-espacio', 'IndustryController::join');
$routes->post('espacios/(:num)/editar', 'IndustryController::update/$1');
$routes->post('espacios/(:num)/miembros/(:num)/eliminar', 'IndustryController::removeMember/$1/$2');
$routes->post('espacios/(:num)/miembros/(:num)/permiso', 'IndustryController::updateMemberPermission/$1/$2');
$routes->post('espacios/(:num)/miembros/(:num)/rol', 'IndustryController::updateMemberRole/$1/$2');
$routes->post('espacios/(:num)/miembros/(:num)/placas/(:num)', 'IndustryController::updateMemberDeviceAccess/$1/$2/$3');
$routes->post('espacios/(:num)/sensores/desbloquear', 'IndustryController::unlockSensors/$1');
$routes->post('espacios/(:num)/clave/recuperar', 'IndustryController::requestPasswordReset/$1');
$routes->get('espacios/clave/(:segment)', 'IndustryController::spacePasswordResetForm/$1');
$routes->post('espacios/clave', 'IndustryController::updateSpacePasswordByToken');
$routes->post('espacios/(:num)/eliminar/solicitar', 'IndustryController::requestDelete/$1');
$routes->get('espacios/eliminar/(:segment)', 'IndustryController::deleteConfirmForm/$1');
$routes->post('espacios/eliminar/(:segment)', 'IndustryController::deleteByToken/$1');

// Invitar por correo a un espacio (flujo tipo "compartir documento"): el admin
// manda el mail y el invitado entra por el link de un solo uso del correo.
$routes->post('espacios/(:num)/invitaciones', 'IndustryController::invite/$1');
$routes->post('espacios/(:num)/invitaciones/(:num)/cancelar', 'IndustryController::cancelInvitation/$1/$2');
$routes->get('invitacion/(:segment)', 'IndustryController::acceptInvitation/$1');
$routes->post('espacios/(:num)/sensores/(:num)/eliminar', 'SensorController::deleteFromSpace/$1/$2');

// Stock por espacio (una lista independiente por cada espacio)
$routes->get('espacios/(:num)/stock', 'StockController::index/$1');
// API de las tablas de stock: cada cambio de la UI se guarda al toque.
$routes->post('espacios/(:num)/stock/tablas', 'StockController::createTable/$1');
$routes->post('espacios/(:num)/stock/importar', 'StockController::importTable/$1');
$routes->post('espacios/(:num)/stock/tablas/(:num)', 'StockController::updateTable/$1/$2');
$routes->post('espacios/(:num)/stock/tablas/(:num)/eliminar', 'StockController::deleteTable/$1/$2');
$routes->post('espacios/(:num)/stock/tablas/(:num)/columnas', 'StockController::createColumn/$1/$2');
$routes->post('espacios/(:num)/stock/tablas/(:num)/columnas/(:num)', 'StockController::updateColumn/$1/$2/$3');
$routes->post('espacios/(:num)/stock/tablas/(:num)/columnas/(:num)/eliminar', 'StockController::deleteColumn/$1/$2/$3');
$routes->post('espacios/(:num)/stock/tablas/(:num)/filas', 'StockController::createRow/$1/$2');
$routes->post('espacios/(:num)/stock/tablas/(:num)/filas/(:num)/eliminar', 'StockController::deleteRow/$1/$2/$3');
$routes->post('espacios/(:num)/stock/tablas/(:num)/celdas/(:num)/(:num)', 'StockController::updateCell/$1/$2/$3/$4');

// Campana del header. El feed es GET para que el polling no rote el CSRF.
$routes->get('notificaciones', 'NotificationController::feed');
$routes->post('notificaciones/leidas', 'NotificationController::markAllRead');
$routes->post('notificaciones/borrar', 'NotificationController::clearAll');

// Centro de ayuda (publico, se abre en pestaña nueva desde el sidebar)
$routes->get('ayuda', 'AyudaController::index');

// ESP32 (rutas especificas antes de la parametrica)
$routes->get('esp32', 'Esp32Controller::index');
$routes->get('esp32/agregar', 'Esp32Controller::createForm');
$routes->post('esp32/agregar', 'Esp32Controller::store');
$routes->post('esp32/(:num)/eliminar', 'Esp32Controller::delete/$1');
$routes->post('esp32/(:num)/sensores', 'SensorController::store/$1');
// Sensores de una placa desde el modal de configuración: cambiar el tipo/etiqueta
// y borrar. El primer (:num) es la placa; el controlador valida que el sensor
// sea de esa placa antes de tocarlo.
$routes->post('esp32/(:num)/sensores/(:num)', 'SensorController::update/$1/$2');
$routes->post('esp32/(:num)/sensores/(:num)/eliminar', 'SensorController::deleteFromDevice/$1/$2');
$routes->get('esp32/(:num)', 'Esp32Controller::show/$1');

// show user
$routes->get('showuser/', 'ShowUserController::index');

// APIs IoT
$routes->get('api/esp32/(:num)/lecturas', 'Esp32Controller::readings/$1');
$routes->post('api/iot/lectura', 'IoTController::ingest'); // ingesta desde ESP32 (MAC + token)

// === Tablero Kanban (uno por ESP32) ===
// El primer (:num) es SIEMPRE el id de la placa: el controlador resuelve el
// espacio a partir de ella y valida la membresía en cada request, así los
// datos de un tablero no se pueden tocar desde otro espacio.
$routes->get('kanban/(:num)/tablero', 'KanbanController::board/$1');
$routes->post('kanban/(:num)/tablero', 'KanbanController::updateBoard/$1');

// Columnas ("orden" va antes que (:num) por claridad de lectura)
$routes->post('kanban/(:num)/columnas', 'KanbanController::createColumn/$1');
$routes->post('kanban/(:num)/columnas/orden', 'KanbanController::reorderColumns/$1');
$routes->post('kanban/(:num)/columnas/(:num)', 'KanbanController::updateColumn/$1/$2');
$routes->post('kanban/(:num)/columnas/(:num)/accion', 'KanbanController::columnAction/$1/$2');

// Tareas
$routes->post('kanban/(:num)/tareas', 'KanbanController::createTask/$1');
$routes->get('kanban/(:num)/tareas/(:num)', 'KanbanController::taskDetail/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)', 'KanbanController::updateTask/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/mover', 'KanbanController::moveTask/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/accion', 'KanbanController::taskAction/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/responsables', 'KanbanController::toggleAssignee/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/etiquetas', 'KanbanController::toggleLabel/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/checklists', 'KanbanController::createChecklist/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/comentarios', 'KanbanController::createComment/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/enlaces', 'KanbanController::createLink/$1/$2');
$routes->post('kanban/(:num)/tareas/(:num)/adjuntos', 'KanbanController::uploadAttachment/$1/$2');

// Sub-recursos de tarea
$routes->post('kanban/(:num)/checklists/(:num)', 'KanbanController::updateChecklist/$1/$2');
$routes->post('kanban/(:num)/checklists/(:num)/items', 'KanbanController::createChecklistItem/$1/$2');
$routes->post('kanban/(:num)/items/(:num)', 'KanbanController::updateChecklistItem/$1/$2');
$routes->post('kanban/(:num)/comentarios/(:num)', 'KanbanController::updateComment/$1/$2');
$routes->post('kanban/(:num)/enlaces/(:num)', 'KanbanController::deleteLink/$1/$2');
$routes->post('kanban/(:num)/adjuntos/(:num)', 'KanbanController::deleteAttachment/$1/$2');

// Etiquetas del tablero
$routes->post('kanban/(:num)/etiquetas', 'KanbanController::createLabel/$1');
$routes->post('kanban/(:num)/etiquetas/(:num)', 'KanbanController::updateLabel/$1/$2');

// Paneles auxiliares
$routes->get('kanban/(:num)/archivo', 'KanbanController::archive/$1');
$routes->get('kanban/(:num)/completadas', 'KanbanController::completed/$1');
