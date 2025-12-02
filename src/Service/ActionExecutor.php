<?php

namespace App\Service;

use App\Entity\Action;
use App\Entity\WorkflowSubject;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ActionExecutor
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {}

    public function execute(Action $action, WorkflowSubject $subject): bool
    {
        return match ($action->getType()) {
            'webhook' => $this->executeWebhook($action, $subject),
            'email' => $this->executeEmail($action, $subject),
            default => false,
        };
    }

    private function executeWebhook(Action $action, WorkflowSubject $subject): bool
    {
        $config = $action->getConfig() ?? [];
        $url = $config['url'] ?? null;

        if (!$url) {
            $this->logger->error('Webhook URL missing', ['action' => $action->getId()]);
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', $url, [
                'json' => [
                    'subject_id' => $subject->getId(),
                    'subject_title' => $subject->getTitle(),
                    'current_place' => $subject->getCurrentPlace(),
                    'data' => $subject->getData(),
                    'timestamp' => (new \DateTimeImmutable())->format('c'),
                ],
            ]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (\Exception $e) {
            $this->logger->error('Webhook failed', [
                'action' => $action->getId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function executeEmail(Action $action, WorkflowSubject $subject): bool
    {
        $config = $action->getConfig() ?? [];
        $to = $config['to'] ?? null;
        $subjectLine = $config['subject'] ?? 'Notification FlowForge';
        $body = $config['body'] ?? 'Le sujet "{{ title }}" est passé à l\'étape "{{ place }}".';

        if (!$to) {
            $this->logger->error('Email recipient missing', ['action' => $action->getId()]);
            return false;
        }

        try {
            // Remplacer les variables dans le body
            $body = str_replace(
                ['{{ title }}', '{{ place }}', '{{ id }}'],
                [$subject->getTitle(), $subject->getCurrentPlace(), $subject->getId()],
                $body
            );

            $subjectLine = str_replace(
                ['{{ title }}', '{{ place }}', '{{ id }}'],
                [$subject->getTitle(), $subject->getCurrentPlace(), $subject->getId()],
                $subjectLine
            );

            $email = (new Email())
                ->from('flowforge@example.com')
                ->to($to)
                ->subject($subjectLine)
                ->text($body);

            $this->mailer->send($email);

            $this->logger->info('Email sent', [
                'to' => $to,
                'subject' => $subjectLine,
            ]);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Email failed', [
                'action' => $action->getId(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}