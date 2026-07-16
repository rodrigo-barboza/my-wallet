<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseStatus: string
{
    case Aberta = 'aberta';
    case Paga = 'paga';
    case Atrasada = 'atrasada';
}
