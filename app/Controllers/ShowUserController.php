<?php

namespace App\Controllers;

use App\Models\UserModel;

class ShowUserController extends BaseController
{
    public function index()
    {
        // Obtener el usuario
        $user = (new UserModel())->find(1);

        // Obtener el nombre y apellido
        $name = $user['nombre'] ?? 'Usuario no encontrado';
        $apellido = $user['apellido'] ?? 'Usuario no encontrado';

        // Retornar la vista con los datos del usuario
        return view('showuser', ['name' => $name, 'apellido' => $apellido]);
    }
}