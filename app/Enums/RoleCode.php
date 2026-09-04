<?php

namespace App\Enums;

enum RoleCode: string
{
    case SiteAdmin = 'site_admin';
    case Founder = 'founder';
    case Administrative = 'administrative';
    case Instructor = 'instructor';
    case Student = 'student';
}
