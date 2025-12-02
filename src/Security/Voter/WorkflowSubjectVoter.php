<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Entity\WorkflowSubject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WorkflowSubjectVoter extends Voter
{
    public const VIEW = 'SUBJECT_VIEW';
    public const EDIT = 'SUBJECT_EDIT';
    public const ASSIGN = 'SUBJECT_ASSIGN';
    public const TRANSITION = 'SUBJECT_TRANSITION';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::ASSIGN, self::TRANSITION])
            && $subject instanceof WorkflowSubject;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Les admins peuvent tout faire
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        /** @var WorkflowSubject $workflowSubject */
        $workflowSubject = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($workflowSubject, $user),
            self::EDIT => $this->canEdit($workflowSubject, $user),
            self::ASSIGN => $this->canAssign($user),
            self::TRANSITION => $this->canTransition($workflowSubject, $user),
            default => false,
        };
    }

    private function canView(WorkflowSubject $subject, User $user): bool
    {
        // Les managers voient tout
        if (in_array('ROLE_MANAGER', $user->getRoles())) {
            return true;
        }

        // Les users voient seulement leurs sujets assignés
        return $subject->getAssignedTo() === $user;
    }

    private function canEdit(WorkflowSubject $subject, User $user): bool
    {
        // Les managers peuvent éditer tout
        if (in_array('ROLE_MANAGER', $user->getRoles())) {
            return true;
        }

        // Les users peuvent éditer seulement leurs sujets
        return $subject->getAssignedTo() === $user;
    }

    private function canAssign(User $user): bool
    {
        // Seuls les managers peuvent assigner
        return in_array('ROLE_MANAGER', $user->getRoles());
    }

    private function canTransition(WorkflowSubject $subject, User $user): bool
    {
        // Les managers peuvent appliquer des transitions sur tout
        if (in_array('ROLE_MANAGER', $user->getRoles())) {
            return true;
        }

        // Les users peuvent appliquer des transitions sur leurs sujets
        return $subject->getAssignedTo() === $user;
    }
}