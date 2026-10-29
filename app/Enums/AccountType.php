<?php

namespace App\Enums;

enum AccountType: string
{
    case Bank = 'bank';
    case Cash = 'cash';
    case CreditCard = 'credit_card';
}
