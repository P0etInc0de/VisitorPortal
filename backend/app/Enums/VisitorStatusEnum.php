<?php

namespace App\Enums;

enum VisitorStatusEnum: string
{
    case Planned = 'Planned';
    case Checked_In = 'Checked_In';
    case Checked_Out = 'Checked_Out';
    case Cancelled = 'Cancelled';
}
