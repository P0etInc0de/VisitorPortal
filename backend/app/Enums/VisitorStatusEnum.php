<?php

namespace App\Enums;

enum VisitorStatusEnum: string
{
    case Planned = 'planned';
    case Canceled = 'canceled';
    case Checked_In = 'checked_in';
    case Checked_Out = 'checked_out';
}
