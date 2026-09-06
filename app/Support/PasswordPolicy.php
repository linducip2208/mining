<?php

namespace App\Support;

use App\Models\Setting;

final class PasswordPolicy
{
    public static function rules(bool $confirmed = true): array
    {
        $rules = ['required', 'string', 'min:'.max(8, (int) Setting::get('security.password_min_length', 8))];
        if (filter_var(Setting::get('security.password_require_uppercase', false), FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[A-Z]/';
        }
        if (filter_var(Setting::get('security.password_require_number', false), FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[0-9]/';
        }
        if (filter_var(Setting::get('security.password_require_symbol', false), FILTER_VALIDATE_BOOLEAN)) {
            $rules[] = 'regex:/[^A-Za-z0-9]/';
        }
        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }
}
