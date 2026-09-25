<?php

// Mensajes de validación en castellano. La app usa el locale "en" por defecto,
// así que se pisan acá los de CodeIgniter (que vienen en inglés). Cada error
// se muestra al lado de su campo, por eso no repiten el nombre del campo.
return [
    'required'           => 'Este campo es obligatorio.',
    'min_length'         => 'Tiene que tener al menos {param} caracteres.',
    'max_length'         => 'No puede superar los {param} caracteres.',
    'exact_length'       => 'Tiene que tener exactamente {param} caracteres.',
    'valid_email'        => 'Ingresá un email válido.',
    'is_unique'          => 'Ese dato ya está registrado.',
    'matches'            => 'Los valores no coinciden.',
    'differs'            => 'Tiene que ser distinto del anterior.',
    'decimal'            => 'Tiene que ser un número.',
    'numeric'            => 'Tiene que ser un número.',
    'integer'            => 'Tiene que ser un número entero.',
    'is_natural'         => 'Tiene que ser un número entero positivo.',
    'is_natural_no_zero' => 'Elegí una opción válida.',
    'in_list'            => 'Elegí una opción válida.',
    'alpha_numeric'      => 'Solo se permiten letras y números.',
    'valid_date'         => 'Ingresá una fecha válida.',
    'valid_url'          => 'Ingresá una dirección web válida.',
    'regex_match'        => 'El formato no es válido.',
];
