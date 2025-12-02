<?php

namespace App\Service;

use App\Entity\WorkflowSubject;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercurePublisher
{
    public function __construct(
        private HubInterface $hub
    ) {}

    public function publishTransition(WorkflowSubject $subject, string $transitionName, string $fromPlace): void
    {
        $update = new Update(
            'workflow/' . $subject->getWorkflow()->getId(),
            json_encode([
                'type' => 'transition',
                'subjectId' => $subject->getId(),
                'subjectTitle' => $subject->getTitle(),
                'transition' => $transitionName,
                'fromPlace' => $fromPlace,
                'toPlace' => $subject->getCurrentPlace(),
                'timestamp' => (new \DateTime())->format('c'),
            ])
        );

        $this->hub->publish($update);
    }

    public function publishSubjectCreated(WorkflowSubject $subject): void
    {
        $update = new Update(
            'workflow/' . $subject->getWorkflow()->getId(),
            json_encode([
                'type' => 'subject_created',
                'subjectId' => $subject->getId(),
                'subjectTitle' => $subject->getTitle(),
                'currentPlace' => $subject->getCurrentPlace(),
                'timestamp' => (new \DateTime())->format('c'),
            ])
        );

        $this->hub->publish($update);
    }

    public function publishDeadlineAlert(WorkflowSubject $subject): void
    {
        $update = new Update(
            'workflow/' . $subject->getWorkflow()->getId(),
            json_encode([
                'type' => 'deadline_alert',
                'subjectId' => $subject->getId(),
                'subjectTitle' => $subject->getTitle(),
                'deadline' => $subject->getDeadline()->format('c'),
                'timestamp' => (new \DateTime())->format('c'),
            ])
        );

        $this->hub->publish($update);
    }
}