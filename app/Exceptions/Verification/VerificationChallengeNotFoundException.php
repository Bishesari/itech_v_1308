<?php

namespace App\Exceptions\Verification;

use Exception;

class VerificationChallengeNotFoundException extends Exception
{
    protected $message = 'Active verification challenge not found.';
}
