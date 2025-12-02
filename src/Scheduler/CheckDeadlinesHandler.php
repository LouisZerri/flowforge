<?php

namespace App\Scheduler;

use App\Repository\WorkflowSubjectRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

#[AsMessageHandler]
class CheckDeadlinesHandler
{
    public function __construct(
        private WorkflowSubjectRepository $subjectRepository,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function __invoke(CheckDeadlinesMessage $message): void
    {
        $now = new \DateTimeImmutable();
        $subjects = $this->subjectRepository->findOverdueSubjects($now);

        if (empty($subjects)) {
            $this->logger->info('Aucune deadline dépassée.');
            return;
        }

        $this->logger->warning(count($subjects) . ' deadline(s) dépassée(s).');

        foreach ($subjects as $subject) {
            $assignedTo = $subject->getAssignedTo();

            if ($assignedTo) {
                $email = (new Email())
                    ->from('flowforge@example.com')
                    ->to($assignedTo->getEmail())
                    ->subject('[URGENT] Deadline dépassée : ' . $subject->getTitle())
                    ->text(sprintf(
                        "Bonjour %s,\n\nLa deadline pour \"%s\" est dépassée depuis le %s.\n\nFlowForge",
                        $assignedTo->getFirstName(),
                        $subject->getTitle(),
                        $subject->getDeadline()->format('d/m/Y H:i')
                    ));

                $this->mailer->send($email);
                $this->logger->info('Email envoyé à ' . $assignedTo->getEmail());
            }
        }
    }
}