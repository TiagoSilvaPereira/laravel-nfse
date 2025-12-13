<?php

namespace App\Helpers;

class Tools
{
    public static function removeNullValues(array $data): array
    {
        return array_filter($data, fn($v) => !is_null($v));
    }
}