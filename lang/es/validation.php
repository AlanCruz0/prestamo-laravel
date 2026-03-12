<?php

return [
    'accepted' => 'El campo :attribute debe ser aceptado.',
    'confirmed' => 'La confirmacion de :attribute no coincide.',
    'current_password' => 'La contrasena actual no es correcta.',
    'digits' => 'El campo :attribute debe tener :digits digitos.',
    'email' => 'El campo :attribute debe ser un correo electronico valido.',
    'max' => [
        'string' => 'El campo :attribute no debe ser mayor a :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser una cadena de texto.',
    'unique' => 'El valor de :attribute ya esta en uso.',

    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'mixed' => 'La :attribute debe contener mayusculas y minusculas.',
        'numbers' => 'La :attribute debe contener al menos un numero.',
        'symbols' => 'La :attribute debe contener al menos un simbolo.',
        'uncompromised' => 'La :attribute ha aparecido en una filtracion de datos. Elige otra diferente.',
    ],

    'attributes' => [
        'name' => 'nombre',
        'email' => 'correo electronico',
        'password' => 'contrasena',
        'password_confirmation' => 'confirmacion de contrasena',
        'current_password' => 'contrasena actual',
        'code' => 'codigo',
        'token' => 'token',
        'g-recaptcha-response' => 'verificacion de reCAPTCHA',
    ],
];
