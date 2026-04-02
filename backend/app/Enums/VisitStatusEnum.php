<?php

namespace App\Enums;

enum VisitStatusEnum: string
{
    case Planned = 'Planned';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
