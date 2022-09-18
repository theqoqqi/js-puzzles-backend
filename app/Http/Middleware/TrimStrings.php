<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware {
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'codeFrames\.\d+\.contents',
        'contents',
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Transform the given value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function transform($key, $value) {
        foreach ($this->except as $pattern) {
            if (preg_match("/^$pattern$/", $key)) {
                return $value;
            }
        }

        return is_string($value) ? trim($value) : $value;
    }
}
