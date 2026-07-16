<?php

declare(strict_types=1);

namespace App\Enums;

enum PurchaseType: string
{
    case Subscription = 'subscription';
    case CreditCard = 'credit_card';
    case Bill = 'bill';
    case Financing = 'financing';
    case Person = 'person';
}
