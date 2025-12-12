<?php

namespace App\Security\Voter;

use App\Entity\Document;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DocumentVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT])
            && $subject instanceof Document;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var Document $document */
        $document = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($document, $user),
            self::EDIT => $this->canEdit($document, $user),
            default => false,
        };
    }

    private function canView(Document $document, User $user): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        if ($document->getCreatedBy() === $user) {
            return true;
        }

        if ($document->getOrganization() && $document->getOrganization() === $user->getOrganization()) {
            return true;
        }

        return false;
    }

    private function canEdit(Document $document, User $user): bool
    {
        if (in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            return true;
        }

        if ($document->getCreatedBy() === $user) {
            return true;
        }

        if (in_array('ROLE_ADMIN_ORG', $user->getRoles()) 
            && $document->getOrganization() === $user->getOrganization()) {
            return true;
        }

        if (in_array('ROLE_VALIDATOR', $user->getRoles()) 
            && $document->getOrganization() === $user->getOrganization()
            && $document->getStatus() === Document::STATUS_REVIEW) {
            return true;
        }

        return false;
    }
}