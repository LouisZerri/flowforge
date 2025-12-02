<?php

namespace App\Command;

use App\Repository\WorkflowSubjectRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Scheduler\Attribute\AsPeriodicTask;

#[AsCommand(
    name: 'app:check-deadlines',
    description: 'Vérifie les deadlines dépassées et envoie des alertes',
)]
#[AsPeriodicTask(frequency: '5 minutes', schedule: 'default')]
class CheckDeadlinesCommand extends Command
{
    public function __construct(
        private WorkflowSubjectRepository $subjectRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTimeImmutable();
        $subjects = $this->subjectRepository->findOverdueSubjects($now);

        if (empty($subjects)) {
            $io->success('Aucune deadline dépassée.');
            return Command::SUCCESS;
        }

        $io->warning(count($subjects) . ' deadline(s) dépassée(s) trouvée(s).');

        foreach ($subjects as $subject) {
            $assignedTo = $subject->getAssignedTo();
            $workflow = $subject->getWorkflow();

            $io->writeln(sprintf(
                '- %s (Workflow: %s, Assigné à: %s, Deadline: %s)',
                $subject->getTitle(),
                $workflow->getName(),
                $assignedTo ? $assignedTo->getEmail() : 'Non assigné',
                $subject->getDeadline()->format('d/m/Y H:i')
            ));

            if ($assignedTo) {
                $email = (new Email())
                    ->from('flowforge@example.com')
                    ->to($assignedTo->getEmail())
                    ->subject('[URGENT] Deadline dépassée : ' . $subject->getTitle())
                    ->text(sprintf(
                        "Bonjour %s,\n\nLa deadline pour \"%s\" est dépassée depuis le %s.\n\nMerci de traiter ce sujet rapidement.\n\nFlowForge",
                        $assignedTo->getFirstName(),
                        $subject->getTitle(),
                        $subject->getDeadline()->format('d/m/Y H:i')
                    ));

                $this->mailer->send($email);
                $io->writeln('  → Email envoyé à ' . $assignedTo->getEmail());
            }
        }

        $io->success('Vérification terminée.');

        return Command::SUCCESS;
    }
}