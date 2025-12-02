<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\TransitionLog;
use App\Entity\WorkflowSubject;
use App\Repository\WorkflowRepository;
use App\Repository\WorkflowSubjectRepository;
use App\Service\DynamicWorkflowBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ActionExecutor;
use App\Service\MercurePublisher;

#[Route('/workflow/{workflowId}/subject')]
final class WorkflowSubjectController extends AbstractController
{
    
    #[Route('/', name: 'app_subject_index', methods: ['GET'])]
    public function index(string $workflowId, WorkflowRepository $workflowRepository, WorkflowSubjectRepository $subjectRepository, DynamicWorkflowBuilder $builder): Response
    {
        $workflow = $workflowRepository->find($workflowId);

        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $user = $this->getUser();
        
        // Admin et Manager voient tout, User voit seulement ses sujets
        if ($this->isGranted('ROLE_MANAGER')) {
            $subjects = $subjectRepository->findBy(['workflow' => $workflow]);
        } else {
            $subjects = $subjectRepository->findBy(['workflow' => $workflow, 'assignedTo' => $user]);
        }

        $availableTransitions = [];
        foreach ($subjects as $subject) {
            $availableTransitions[$subject->getId()] = [];
            foreach ($workflow->getTransitions() as $transition) {
                if ($builder->canTransition($workflow, $subject, $transition->getName())) {
                    $availableTransitions[$subject->getId()][] = $transition;
                }
            }
        }

        return $this->render('workflow_subject/index.html.twig', [
            'workflow' => $workflow,
            'subjects' => $subjects,
            'availableTransitions' => $availableTransitions,
        ]);
    }

    #[Route('/new', name: 'app_subject_new', methods: ['GET', 'POST'])]
    public function new(string $workflowId, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($workflowId);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            
            if ($title) {
                $subject = new WorkflowSubject();
                $subject->setTitle($title);
                $subject->setWorkflow($workflow);
                $subject->setCurrentPlace($workflow->getInitialPlace());
                $subject->setCreatedAt(new \DateTimeImmutable());

                $em->persist($subject);
                $em->flush();

                $this->addFlash('success', 'Sujet créé !');
                return $this->redirectToRoute('app_subject_index', ['workflowId' => $workflowId]);
            }

            $this->addFlash('error', 'Le titre est obligatoire.');
        }

        return $this->render('workflow_subject/new.html.twig', [
            'workflow' => $workflow,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_subject_edit', methods: ['GET', 'POST'])]
    public function edit(string $workflowId, string $id, WorkflowRepository $workflowRepository, WorkflowSubjectRepository $subjectRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($workflowId);
        $subject = $subjectRepository->find($id);

        if (!$workflow || !$subject) {
            throw $this->createNotFoundException('Ressource non trouvée');
        }

        if ($request->isMethod('POST')) {
            $title = $request->request->get('title');
            $dataJson = $request->request->get('data');

            if ($title) {
                $subject->setTitle($title);
            }

            if ($dataJson) {
                $data = json_decode($dataJson, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $subject->setData($data);
                } else {
                    $this->addFlash('error', 'JSON invalide');
                    return $this->redirectToRoute('app_subject_edit', ['workflowId' => $workflowId, 'id' => $id]);
                }
            } else {
                $subject->setData(null);
            }

            $subject->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Sujet modifié !');
            return $this->redirectToRoute('app_subject_show', ['workflowId' => $workflowId, 'id' => $id]);
        }

        return $this->render('workflow_subject/edit.html.twig', [
            'workflow' => $workflow,
            'subject' => $subject,
        ]);
    }

    #[Route('/{id}', name: 'app_subject_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(string $workflowId, string $id, WorkflowRepository $workflowRepository, WorkflowSubjectRepository $subjectRepository, DynamicWorkflowBuilder $builder, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($workflowId);
        $subject = $subjectRepository->find($id);

        if (!$workflow || !$subject) {
            throw $this->createNotFoundException('Ressource non trouvée');
        }

        // Vérifier la permission
        $this->denyAccessUnlessGranted('SUBJECT_VIEW', $subject);

        $availableTransitions = [];
        foreach ($workflow->getTransitions() as $transition) {
            if ($builder->canTransition($workflow, $subject, $transition->getName())) {
                $availableTransitions[] = $transition;
            }
        }

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('workflow_subject/show.html.twig', [
            'workflow' => $workflow,
            'subject' => $subject,
            'availableTransitions' => $availableTransitions,
            'users' => $users,
            'canAssign' => $this->isGranted('SUBJECT_ASSIGN', $subject),
            'canTransition' => $this->isGranted('SUBJECT_TRANSITION', $subject),
        ]);
    }

    #[Route('/{id}/apply', name: 'app_subject_apply', methods: ['POST'])]
    public function apply(
        string $workflowId,
        string $id,
        Request $request,
        WorkflowSubjectRepository $subjectRepository,
        DynamicWorkflowBuilder $builder,
        ActionExecutor $actionExecutor,
        MercurePublisher $mercurePublisher,
        EntityManagerInterface $em
    ): Response {

        $subject = $subjectRepository->find($id);
        if (!$subject) {
            throw $this->createNotFoundException('Sujet non trouvé');
        }
        $this->denyAccessUnlessGranted('SUBJECT_TRANSITION', $subject);
        
        // Cherche dans POST ou GET
        $transitionName = $request->request->get('transition') ?? $request->query->get('transition');

        if (!$subject) {
            throw $this->createNotFoundException('Sujet non trouvé');
        }

        $this->denyAccessUnlessGranted('SUBJECT_TRANSITION', $subject);

        $transitionName = $request->request->get('transition') ?? $request->query->get('transition');
        $workflowEntity = $subject->getWorkflow();

        if (!$builder->canTransition($workflowEntity, $subject, $transitionName)) {
            $this->addFlash('error', 'Transition impossible.');
            return $this->redirectToRoute('app_subject_show', [
                'workflowId' => $workflowId,
                'id' => $id
            ]);
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

        // Publier la notification temps réel
        try {
            $mercurePublisher->publishTransition($subject, $transitionName, $fromPlace);
        } catch (\Exception $e) {
            file_put_contents('/tmp/mercure_error.log', $e->getMessage() . "
", FILE_APPEND);
        }

        // Exécuter les actions
        foreach ($workflowEntity->getTransitions() as $transition) {
            if ($transition->getName() === $transitionName) {
                foreach ($transition->getActions() as $action) {
                    $actionExecutor->execute($action, $subject);
                }
                break;
            }
        }

        $this->addFlash('success', 'Transition "' . $transitionName . '" appliquée.');

        return $this->redirectToRoute('app_subject_show', [
            'workflowId' => $workflowId,
            'id' => $id
        ]);
    }

    #[Route('/{id}/assign', name: 'app_subject_assign', methods: ['POST'])]
    public function assign(
        string $workflowId,
        string $id,
        WorkflowRepository $workflowRepository,
        WorkflowSubjectRepository $subjectRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        
        $workflow = $workflowRepository->find($workflowId);
        $subject = $subjectRepository->find($id);

        if (!$workflow || !$subject) {
            throw $this->createNotFoundException('Ressource non trouvée');
        }

        // Vérifier la permission
        $this->denyAccessUnlessGranted('SUBJECT_ASSIGN', $subject);

        if ($this->isCsrfTokenValid('assign'.$subject->getId(), $request->request->get('_token'))) {
            $userId = $request->request->get('user_id');
            
            if ($userId) {
                $user = $em->getRepository(User::class)->find($userId);
                $subject->setAssignedTo($user);
            } else {
                $subject->setAssignedTo(null);
            }
            
            $subject->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Assignation mise à jour !');
        }

        return $this->redirectToRoute('app_subject_show', ['workflowId' => $workflowId, 'id' => $id]);
    }
    
    #[Route('/{id}/deadline', name: 'app_subject_deadline', methods: ['POST'])]
    public function setDeadline(
        string $workflowId,
        string $id,
        WorkflowRepository $workflowRepository,
        WorkflowSubjectRepository $subjectRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        
        $workflow = $workflowRepository->find($workflowId);
        $subject = $subjectRepository->find($id);

        if (!$workflow || !$subject) {
            throw $this->createNotFoundException('Ressource non trouvée');
        }

        $this->denyAccessUnlessGranted('SUBJECT_EDIT', $subject);

        if ($this->isCsrfTokenValid('deadline'.$subject->getId(), $request->request->get('_token'))) {
            $deadlineStr = $request->request->get('deadline');
            
            if ($deadlineStr) {
                $subject->setDeadline(new \DateTimeImmutable($deadlineStr));
            } else {
                $subject->setDeadline(null);
            }
            
            $subject->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Deadline mise à jour !');
        }

        return $this->redirectToRoute('app_subject_show', ['workflowId' => $workflowId, 'id' => $id]);
    }

}