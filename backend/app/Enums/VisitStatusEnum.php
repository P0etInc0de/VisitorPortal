<?php

namespace App\Enums;

enum VisitStatusEnum:String
{
    case Planned = 'Planned';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
