<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Dto;

class PasswordResetCode
{
    private string $code;

    private string $codeHash;

    private int $expiresIn;

    /**
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    /**
     * @param string $code
     * @return PasswordResetCode
     */
    public function setCode(string $code): PasswordResetCode
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @param string $codeHash
     * @return PasswordResetCode
     */
    public function setCodeHash(string $codeHash): PasswordResetCode
    {
        $this->codeHash = $codeHash;
        return $this;
    }

    /**
     * @return int
     */
    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * @param int $expiresIn
     * @return PasswordResetCode
     */
    public function setExpiresIn(int $expiresIn): PasswordResetCode
    {
        $this->expiresIn = $expiresIn;
        return $this;
    }
}
