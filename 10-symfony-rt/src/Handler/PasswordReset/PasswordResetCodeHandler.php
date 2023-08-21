<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\PasswordReset;

use App\Dto\PasswordResetCode;
use App\Entity\PasswordReset;
use App\Entity\User;
use App\Repository\PasswordResetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

class PasswordResetCodeHandler
{
    /**
     * Symfony hasher name from config/packages/security.yml
     */
    public const HASHER_NAME = 'password_reset';

    /**
     * @var PasswordResetCode Latest created code for testing purposes.
     */
    public static PasswordResetCode $codeForTesting;

    private int $codeExpiration = 60 * 60;

    /**
     * Fake timestamp for current time for testing purposes.
     */
    private ?int $testCurrentTime = null;

    public function __construct(
        private PasswordHasherFactoryInterface $hasherFactory,
        private EntityManagerInterface $entityManager,
        private PasswordResetRepository $passwordResetRepository,
        $codeExpiration = null,
    )
    {
        if (null !== $codeExpiration) {
            $this->codeExpiration = $codeExpiration;
        }
    }

    public function generate(User $user): PasswordResetCode
    {
        // delete all existing codes for a user
        $this->clearForUser($user);

        // generate new code
        $code = new PasswordResetCode();
        $code->setCode((string)mt_rand(100000, 999999));
        $code->setCodeHash($this->getPasswordHasher()->hash($code->getCode()));
        $code->setExpiresIn($this->getCodeExpiration());

        static::$codeForTesting = $code;

        // store it
        $passwordReset = (new PasswordReset())
            ->setUserId($user->getUserId())
            ->setCode($code->getCodeHash());
        $this->entityManager->persist($passwordReset);
        $this->entityManager->flush();

        return $code;
    }

    /**
     * Validates a given code for a user. Throws an exception if not successful.
     *
     * @param User $user
     * @param string $code
     * @return bool
     */
    public function validateCode(User $user, string $code): bool
    {
        $passwordReset = $this->passwordResetRepository->findLatestForUser($user->getUserId());

        if (null === $passwordReset) {
            return false;
        }

        $timePassed = $this->getCurrentTime() - $passwordReset->getCreatedDate()->getTimestamp();
        if ($timePassed >= $this->getCodeExpiration()) {
            return false;
        }

        if (!$this->getPasswordHasher()->verify($passwordReset->getCode(), $code)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes all existing codes for a user.
     *
     * @param User $user
     * @return void
     */
    public function clearForUser(User $user): void
    {
        $this->passwordResetRepository->clearForUser($user->getUserId());
    }

    protected function getPasswordHasher(): \Symfony\Component\PasswordHasher\PasswordHasherInterface
    {
        return $this->hasherFactory->getPasswordHasher(self::HASHER_NAME);
    }

    /**
     * @return int
     */
    public function getCodeExpiration(): int
    {
        return $this->codeExpiration;
    }

    public function setTestCurrentTime(?int $testCurrentTime): void
    {
        $this->testCurrentTime = $testCurrentTime;
    }

    protected function getCurrentTime(): int
    {
        if (is_int($this->testCurrentTime)) {
            return $this->testCurrentTime;
        }

        return time();
    }
}
