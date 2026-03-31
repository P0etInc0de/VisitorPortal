<?php

namespace App\Enums;

enum GenderEnum: String
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';

    case Not_Specified = 'not specified';
}
