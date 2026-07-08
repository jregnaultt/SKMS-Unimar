<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UnimarEmail implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! str_ends_with(strtolower($value), '@unimar.edu.ve')) {
            $fail('mira porfa tiene que ser ese tipo de correo nada mas');
        }
    }
}
