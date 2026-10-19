<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class FilterValidation
{
    /** Evita volver a la misma URL inválida cuando los filtros llegan por GET. */
    public static function validate(Request $request, array $rules, string $fallbackRoute): array
    {
        $validator = Validator::make($request->only(array_keys($rules)), $rules, [
            'string'      => 'El filtro :attribute debe ser un texto.',
            'integer'     => 'El filtro :attribute debe ser un número entero.',
            'date_format' => 'El filtro :attribute debe ser una fecha válida (AAAA-MM-DD).',
            'min'         => 'El filtro :attribute debe ser al menos :min.',
            'in'          => 'El filtro :attribute no contiene una opción válida.',
        ]);

        if ($validator->fails()) {
            throw (new ValidationException($validator))->redirectTo(route($fallbackRoute));
        }

        return $validator->validated();
    }
}
