<?php

namespace App\Exceptions\Verification;

use Exception;

class ActiveVerificationChallengeException extends Exception
{
    protected $message = 'Verification challenge is still active.';
}
