<?php

namespace App\Support;

use Illuminate\Http\Request;

class PerPage
{
    public const OPTIONS = [10, 25, 50, 100];

    public static function from(Request $request, int $default = 10): int
    {
        $requested = (int) $request->input('per_page', $default);

        return in_array($requested, self::OPTIONS, true) ? $requested : $default;
    }
}
