<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Handler\PasswordReset;

use App\Dto\PasswordResetCode;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetHandler
{

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private PasswordResetCodeHandler $codeHandler,
        private UserPasswordHasherInterface $hasher,
        private MailerInterface $mailer,
    )
    {
    }

    /**
     * Request a reset code for a user with given email address
     *
     * @param string $email
     * @return PasswordResetCode
     * @throws PasswordResetException Logical exceptions thrown by this handler
     */
    public function forgot(string $email): PasswordResetCode
    {
        $user = $this->findUser($email);
        $code = $this->codeHandler->generate($user);
        $this->sendCode($user, $code);

        return $code;
    }

    /**
     * Resets a password for a user if a code is correct.
     *
     * @throws PasswordResetException
     */
    public function reset(string $email, string $code, string $password): void
    {
        $user = $this->findUser($email);

        if (!$this->codeHandler->validateCode($user, $code)) {
            throw new PasswordResetException(
                'Invalid reset code. Possible reasons: expiration, typo, missing code',
                PasswordResetException::CODE_INVALID,
            );
        }

        $this->codeHandler->clearForUser($user);
        $user->setPassword(
            $this->hasher->hashPassword($user, $password)
        );
        $this->entityManager->flush();
    }

    protected function sendCode(User $user, PasswordResetCode $code): void
    {
        $email = (new TemplatedEmail())
            ->to($user->getEmail())
            ->priority(Email::PRIORITY_HIGH)
            ->subject('Here’s your reset code for DynamicDocuments Renewal Tool')
            ->htmlTemplate('emails/password-reset-code.html.twig')
            ->context([
                'code' => $code->getCode(),
                'expiresIn' => $code->getExpiresIn(),
                'expiresInMinutes' => $code->getExpiresIn() / 60,
            ]);

        $this->mailer->send($email);
    }

    /**
     * @throws PasswordResetException
     */
    protected function findUser(string $email): User
    {
        $user = $this->userRepository->findOneBy(compact('email'));
        if (!$user) {
            throw new PasswordResetException(
                'No user was found with the given email address.',
                PasswordResetException::USER_NOT_FOUND,
            );
        }

        return $user;
    }
}
