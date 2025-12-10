<?php

namespace App\Enums;

enum NfseEnvironment: int
{
    case PRODUCTION = 1;
    case HOMOLOGATION = 2;

    public function label(): string
    {
        return match($this) {
            self::PRODUCTION => 'Production',
            self::HOMOLOGATION => 'Homologation',
        };
    }
}