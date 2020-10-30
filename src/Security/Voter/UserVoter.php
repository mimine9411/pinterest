<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{

    const SHOW = 'show';
    const CHANGE_PASSWORD = 'change_password';

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::SHOW, self::CHANGE_PASSWORD])
            && $subject instanceof \App\Entity\User;;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case self::SHOW:
                return $this->canShow($user, $subject);
            case self::CHANGE_PASSWORD:
                return $this->canChangePassword($user, $subject);
        }
        throw new \LogicException('This code should not be reached!');
    }

    public function canShow(UserInterface $user, User $subject) {
        if($user->getId() === $subject->getId()) {
            return true;
        }
        return false;
    }

    public function canChangePassword(UserInterface $user, User $subject) {
        return $this->canShow($user, $subject);
    }
}
