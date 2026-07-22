<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Aberta = 'aberta';
    case Fechada = 'fechada';
    case Paga = 'paga';
    case ParcialmentePaga = 'parcialmente_paga';
    case Atrasada = 'atrasada';
}
