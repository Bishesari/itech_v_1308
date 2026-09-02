<?php

namespace App\Enums;

enum VerificationPurpose: string
{
    case Registration = 'registration';
    case PasswordReset = 'password_reset';

}
