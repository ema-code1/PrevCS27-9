<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Landing::index');
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
$routes->get('forgot-password', 'Auth::forgotPassword');
$routes->post('forgot-password', 'Auth::sendResetLink');
$routes->get('reset-password/(:segment)', 'Auth::resetPassword/$1');
$routes->post('reset-password', 'Auth::updatePassword');
$routes->get('admin-account', 'Account::index');
$routes->post('admin-account/update', 'Account::update');
