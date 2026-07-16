<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Aberta = 'aberta';
    case Fechada = 'fechada';
    case Paga = 'paga';
    case Atrasada = 'atrasada';
}
