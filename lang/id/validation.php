<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'boolean' => ':attribute harus berupa pilihan ya atau tidak.',
    'integer' => ':attribute harus berupa angka bulat.',
    'numeric' => ':attribute harus berupa angka.',
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'integer' => ':attribute minimal :min.',
    ],
    'max' => [
        'numeric' => ':attribute maksimal :max.',
        'integer' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'url' => ':attribute harus berupa URL yang valid.',
    'regex' => 'Format :attribute tidak valid.',
    'attributes' => array_merge([
        'value' => 'Nilai pengaturan',
    ], \App\Support\ValidationAttributes::all()),
];
