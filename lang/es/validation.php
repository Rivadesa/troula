<?php

/*
 * Mensajes de validación en español.
 *
 * // DECISIÓN: APP_LOCALE ya era 'es' pero no existía carpeta lang/, así que
 * Laravel mostraba la clave en crudo ("validation.required") en cualquier error
 * del formulario público. Se incluyen las reglas que usa la aplicación; el resto
 * seguiría cayendo en la clave, así que conviene añadirlas si se usan reglas nuevas.
 */

return [
    'accepted' => 'Debes aceptar :attribute.',
    'after' => ':Attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => ':Attribute debe ser una fecha igual o posterior a :date.',
    'alpha_dash' => ':Attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => ':Attribute solo puede contener letras y números.',
    'before' => ':Attribute debe ser una fecha anterior a :date.',
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => ':Attribute no es una fecha válida.',
    'declined' => ':Attribute debe estar rechazado.',
    'different' => ':Attribute y :other deben ser distintos.',
    'digits' => ':Attribute debe tener :digits dígitos.',
    'email' => ':Attribute debe ser una dirección de correo válida.',
    'exists' => ':Attribute no es una opción válida.',
    'image' => ':Attribute debe ser una imagen.',
    'in' => ':Attribute no es una opción válida.',
    'integer' => ':Attribute debe ser un número entero.',
    'max' => [
        'array' => ':Attribute no puede tener más de :max elementos.',
        'file' => ':Attribute no puede pesar más de :max kilobytes.',
        'numeric' => ':Attribute no puede ser mayor que :max.',
        'string' => ':Attribute no puede tener más de :max caracteres.',
    ],
    'mimes' => ':Attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => ':Attribute debe tener al menos :min elementos.',
        'file' => ':Attribute debe pesar al menos :min kilobytes.',
        'numeric' => ':Attribute debe ser al menos :min.',
        'string' => ':Attribute debe tener al menos :min caracteres.',
    ],
    'not_in' => ':Attribute no es una opción válida.',
    'numeric' => ':Attribute debe ser un número.',
    'present' => 'El campo :attribute debe estar presente.',
    'regex' => 'El formato de :attribute no es válido.',
    'required' => 'Necesitamos :attribute.',
    'required_if' => 'Necesitamos :attribute cuando :other es :value.',
    'same' => ':Attribute y :other deben coincidir.',
    'size' => [
        'file' => ':Attribute debe pesar :size kilobytes.',
        'numeric' => ':Attribute debe ser :size.',
        'string' => ':Attribute debe tener :size caracteres.',
    ],
    'string' => ':Attribute debe ser texto.',
    'unique' => ':Attribute ya está en uso.',
    'url' => ':Attribute debe ser una URL válida.',

    /*
     * Nombres legibles de los campos. Las propiedades del configurador van en
     * camelCase, así que hay que nombrarlas tal cual.
     */
    'attributes' => [
        'clienteNombre' => 'tu nombre y apellidos',
        'clienteEmail' => 'tu email',
        'clienteTelefono' => 'tu teléfono',
        'clienteDni' => 'tu DNI o NIE',
        'clienteDireccion' => 'tu dirección',
        'aceptoLopd' => 'la política de privacidad',
        'acepto' => 'el contrato',
        'fecha' => 'la fecha del evento',
        'concello' => 'el concello',
        'lugarEvento' => 'el lugar del evento',
        'observaciones' => 'las observaciones',
        'experienciaId' => 'una experiencia',
        'turno' => 'el turno',
    ],
];
