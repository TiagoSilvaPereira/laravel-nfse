<?php

namespace App\Enums;

enum NfseStatus: string
{
    case DRAFT = 'draft';
    case PROCESSING = 'processing';
    case AUTHORIZED = 'authorized';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
}