<?php

namespace App\Enums;

enum VisitorStatusEnum:String
{
    case Planned = 'Planned';
    case Checked_In = 'Checked_In';
    case Checked_Out = 'Checked_Out';
    case Cancelled = 'Cancelled';
}
