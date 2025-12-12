<?php

namespace App\Security\Voter;

use App\Entity\Template;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TemplateVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Template;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Template $template */
        $template = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($template, $user),
            self::EDIT => $this->canEdit($template, $user),
            default => false,
        };
    }

    private function canView(Template $template, User $user): bool
    {
        // Super admin voit tout
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // Templates globaux visibles par tous
        if ($template->getVisibility() === 'global') {
            return true;
        }

        // Templates privés visibles par l'organisation
        if ($template->getOrganization() === $user->getOrganization()) {
            return true;
        }

        return false;
    }

    private function canEdit(Template $template, User $user): bool
    {
        // Super admin peut tout éditer
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        // Le créateur peut éditer
        if ($template->getCreatedBy() === $user) {
            return true;
        }

        // Admin org peut éditer les templates de son organisation
        if (in_array('ROLE_ADMIN_ORG', $user->getRoles()) 
            && $template->getOrganization() === $user->getOrganization()) {
            return true;
        }

        return false;
    }
}