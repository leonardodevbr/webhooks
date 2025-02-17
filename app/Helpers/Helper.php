<?php

namespace App\Helpers;

class Helper
{
    public static function generateShortHash(int $length = 11): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(8))), 0, $length);
    }
}
