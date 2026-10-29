<?php

namespace App\Enums;

enum BillType: string
{
    case Electricity = 'electricity';
    case Water = 'water';
    case NaturalGas = 'natural_gas';
    case Phone = 'phone';
    case Internet = 'internet';
    case Other = 'other';
}
