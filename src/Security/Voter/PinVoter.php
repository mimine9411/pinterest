<?php

namespace App\Security\Voter;

use App\Entity\Pin;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class PinVoter extends Voter
{
    const SHOW = 'show';
    const EDIT = 'edit';
    const DELETE = 'delete';
    const CREATE = 'create';

    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    protected function supports($attribute, $subject)
    {
        return in_array($attribute, [self::SHOW, self::EDIT, self::DELETE, self::CREATE])
            && $subject instanceof \App\Entity\Pin;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }
        $pin = $subject;

        switch ($attribute) {
            case self::SHOW:
                return $this->canShow($pin, $user);
            case self::EDIT:
                return $this->canEdit($pin, $user);
            case self::DELETE:
                return $this->canDelete($pin, $user);
            case self::CREATE:
                return $this->canCreate($user);
        }
        throw new \LogicException('This code should not be reached!');
    }

    public function canShow(Pin $pin, UserInterface $user)
    {
         if($this->canEdit($pin, $user) | $this->security->isGranted('ROLE_ADMIN')) {
             return true;
         }
         return true;
    }

    public function canEdit(Pin $pin, UserInterface $user)
    {
        return($user === $pin->getUser() | $this->security->isGranted('ROLE_ADMIN'));
    }

    public function canDelete(Pin $pin, UserInterface $user)
    {
        if($this->canEdit($pin, $user)) {
            return true;
        }
        return false;
    }

    public function canCreate(UserInterface $user)
    {
        if($user->isVerified() | $this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        return false;
    }
}
