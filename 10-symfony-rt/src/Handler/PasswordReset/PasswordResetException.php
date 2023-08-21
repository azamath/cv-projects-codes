<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\PasswordReset;

class PasswordResetException extends \Exception
{
    public const USER_NOT_FOUND = 1;
    public const CODE_INVALID = 2;
}
