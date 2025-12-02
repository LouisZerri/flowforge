<?php

namespace App\Controller\Api;

use App\Entity\WorkflowSubject;
use App\Entity\TransitionLog;
use App\Repository\WorkflowRepository;
use App\Repository\WorkflowSubjectRepository;
use App\Repository\UserRepository;
use App\Service\ActionExecutor;
use App\Service\DynamicWorkflowBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class WorkflowApiController extends AbstractController
{
    #[Route('/workflows', name: 'api_workflows_list', methods: ['GET'])]
    public function listWorkflows(WorkflowRepository $workflowRepository): JsonResponse
    {
        $workflows = $workflowRepository->findAll();

        $data = array_map(fn($w) => [
            'id' => $w->getId(),
            'name' => $w->getName(),
            'description' => $w->getDescription(),
            'initialPlace' => $w->getInitialPlace(),
            'createdAt' => $w->getCreatedAt()->format('c'),
        ], $workflows);

        return new JsonResponse($data);
    }

    #[Route('/workflows/{id}', name: 'api_workflows_show', methods: ['GET'])]
    public function showWorkflow(string $id, WorkflowRepository $workflowRepository): JsonResponse
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            return new JsonResponse(['error' => 'Workflow non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $places = array_map(fn($p) => [
            'id' => $p->getId(),
            'name' => $p->getName(),
            'label' => $p->getLabel(),
        ], $workflow->getPlaces()->toArray());

        $transitions = array_map(fn($t) => [
            'id' => $t->getId(),
            'name' => $t->getName(),
            'label' => $t->getLabel(),
            'fromPlace' => $t->getFromPlace()->getName(),
            'toPlace' => $t->getToPlace()->getName(),
            'condition' => $t->getCondition(),
        ], $workflow->getTransitions()->toArray());

        return new JsonResponse([
            'id' => $workflow->getId(),
            'name' => $workflow->getName(),
            'description' => $workflow->getDescription(),
            'initialPlace' => $workflow->getInitialPlace(),
            'places' => $places,
            'transitions' => $transitions,
            'createdAt' => $workflow->getCreatedAt()->format('c'),
        ]);
    }

    #[Route('/workflows/{id}/subjects', name: 'api_subjects_list', methods: ['GET'])]
    public function listSubjects(string $id, WorkflowRepository $workflowRepository, WorkflowSubjectRepository $subjectRepository): JsonResponse
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            return new JsonResponse(['error' => 'Workflow non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $subjects = $subjectRepository->findBy(['workflow' => $workflow]);

        $data = array_map(fn($s) => [
            'id' => $s->getId(),
            'title' => $s->getTitle(),
            'currentPlace' => $s->getCurrentPlace(),
            'data' => $s->getData(),
            'assignedTo' => $s->getAssignedTo() ? [
                'id' => $s->getAssignedTo()->getId(),
                'email' => $s->getAssignedTo()->getEmail(),
                'name' => $s->getAssignedTo()->getFirstName() . ' ' . $s->getAssignedTo()->getLastName(),
            ] : null,
            'deadline' => $s->getDeadline()?->format('c'),
            'createdAt' => $s->getCreatedAt()->format('c'),
            'updatedAt' => $s->getUpdatedAt()->format('c'),
        ], $subjects);

        return new JsonResponse($data);
    }

    #[Route('/workflows/{id}/subjects', name: 'api_subjects_create', methods: ['POST'])]
    public function createSubject(string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            return new JsonResponse(['error' => 'Workflow non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['title'])) {
            return new JsonResponse(['error' => 'Le champ "title" est requis'], Response::HTTP_BAD_REQUEST);
        }

        $subject = new WorkflowSubject();
        $subject->setTitle($payload['title']);
        $subject->setWorkflow($workflow);
        $subject->setCurrentPlace($workflow->getInitialPlace());
        $subject->setData($payload['data'] ?? []);
        $subject->setCreatedAt(new \DateTimeImmutable());
        $subject->setUpdatedAt(new \DateTimeImmutable());

        if (isset($payload['deadline'])) {
            $subject->setDeadline(new \DateTimeImmutable($payload['deadline']));
        }

        $em->persist($subject);
        $em->flush();

        return new JsonResponse([
            'id' => $subject->getId(),
            'title' => $subject->getTitle(),
            'currentPlace' => $subject->getCurrentPlace(),
            'data' => $subject->getData(),
            'createdAt' => $subject->getCreatedAt()->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/subjects/{id}', name: 'api_subjects_show', methods: ['GET'])]
    public function showSubject(string $id, WorkflowSubjectRepository $subjectRepository): JsonResponse
    {
        $subject = $subjectRepository->find($id);

        if (!$subject) {
            return new JsonResponse(['error' => 'Sujet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $logs = array_map(fn($l) => [
            'transitionName' => $l->getTransitionName(),
            'fromPlace' => $l->getFromPlace(),
            'toPlace' => $l->getToPlace(),
            'createdAt' => $l->getCreatedAt()->format('c'),
        ], $subject->getLogs()->toArray());

        return new JsonResponse([
            'id' => $subject->getId(),
            'title' => $subject->getTitle(),
            'currentPlace' => $subject->getCurrentPlace(),
            'data' => $subject->getData(),
            'assignedTo' => $subject->getAssignedTo() ? [
                'id' => $subject->getAssignedTo()->getId(),
                'email' => $subject->getAssignedTo()->getEmail(),
                'name' => $subject->getAssignedTo()->getFirstName() . ' ' . $subject->getAssignedTo()->getLastName(),
            ] : null,
            'deadline' => $subject->getDeadline()?->format('c'),
            'logs' => $logs,
            'createdAt' => $subject->getCreatedAt()->format('c'),
            'updatedAt' => $subject->getUpdatedAt()->format('c'),
        ]);
    }

    #[Route('/subjects/{id}/transitions', name: 'api_subjects_transitions', methods: ['GET'])]
    public function availableTransitions(string $id, WorkflowSubjectRepository $subjectRepository, DynamicWorkflowBuilder $builder): JsonResponse
    {
        $subject = $subjectRepository->find($id);

        if (!$subject) {
            return new JsonResponse(['error' => 'Sujet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $workflow = $subject->getWorkflow();
        $available = [];

        foreach ($workflow->getTransitions() as $transition) {
            if ($builder->canTransition($workflow, $subject, $transition->getName())) {
                $available[] = [
                    'name' => $transition->getName(),
                    'label' => $transition->getLabel(),
                ];
            }
        }

        return new JsonResponse($available);
    }

    #[Route('/subjects/{id}/apply', name: 'api_subjects_apply', methods: ['POST'])]
    public function applyTransition(
        string $id,
        WorkflowSubjectRepository $subjectRepository,
        DynamicWorkflowBuilder $builder,
        ActionExecutor $actionExecutor,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $subject = $subjectRepository->find($id);

        if (!$subject) {
            return new JsonResponse(['error' => 'Sujet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (!isset($payload['transition'])) {
            return new JsonResponse(['error' => 'Le champ "transition" est requis'], Response::HTTP_BAD_REQUEST);
        }

        $transitionName = $payload['transition'];
        $workflowEntity = $subject->getWorkflow();

        if (!$builder->canTransition($workflowEntity, $subject, $transitionName)) {
            return new JsonResponse(['error' => 'Transition impossible'], Response::HTTP_BAD_REQUEST);
        }

        $fromPlace = $subject->getCurrentPlace();

        $workflow = $builder->build($workflowEntity);
        $workflow->apply($subject, $transitionName);
        $subject->setUpdatedAt(new \DateTimeImmutable());

        $log = new TransitionLog();
        $log->setSubject($subject);
        $log->setTransitionName($transitionName);
        $log->setFromPlace($fromPlace);
        $log->setToPlace($subject->getCurrentPlace());
        $log->setCreatedAt(new \DateTimeImmutable());

        $em->persist($log);
        $em->flush();

        // Exécuter les actions
        foreach ($workflowEntity->getTransitions() as $t) {
            if ($t->getName() === $transitionName) {
                foreach ($t->getActions() as $action) {
                    $actionExecutor->execute($action, $subject);
                }
                break;
            }
        }

        return new JsonResponse([
            'id' => $subject->getId(),
            'title' => $subject->getTitle(),
            'currentPlace' => $subject->getCurrentPlace(),
            'previousPlace' => $fromPlace,
            'transition' => $transitionName,
        ]);
    }

    #[Route('/subjects/{id}/assign', name: 'api_subjects_assign', methods: ['PUT'])]
    public function assignSubject(string $id, WorkflowSubjectRepository $subjectRepository, UserRepository $userRepository, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $subject = $subjectRepository->find($id);

        if (!$subject) {
            return new JsonResponse(['error' => 'Sujet non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $payload = json_decode($request->getContent(), true);

        if (isset($payload['userId'])) {
            $user = $userRepository->find($payload['userId']);
            if (!$user) {
                return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $subject->setAssignedTo($user);
        } else {
            $subject->setAssignedTo(null);
        }

        $subject->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return new JsonResponse([
            'id' => $subject->getId(),
            'title' => $subject->getTitle(),
            'assignedTo' => $subject->getAssignedTo() ? [
                'id' => $subject->getAssignedTo()->getId(),
                'email' => $subject->getAssignedTo()->getEmail(),
                'name' => $subject->getAssignedTo()->getFirstName() . ' ' . $subject->getAssignedTo()->getLastName(),
            ] : null,
        ]);
    }
}