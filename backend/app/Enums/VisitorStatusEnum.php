<?php

namespace App\Enums;

enum VisitorStatusEnum: string
{
    case Planned = 'planned';
    case Cancelled = 'cancelled';
    case Checked_In = 'checked_in';
    case Checked_Out = 'checked_out';
}
