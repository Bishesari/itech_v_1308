<?php

namespace App\Enums;

enum RoleScope: string
{
    case System = 'system';
    case Institute = 'institute';
    case Branch = 'branch';
}
