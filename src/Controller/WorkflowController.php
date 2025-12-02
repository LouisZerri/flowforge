<?php

namespace App\Controller;

use App\Entity\Workflow;
use App\Form\WorkflowType;
use App\Repository\WorkflowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workflow')]
final class WorkflowController extends AbstractController
{
    #[Route('', name: 'app_workflow_index', methods: ['GET'])]
    public function index(WorkflowRepository $workflowRepository): Response
    {
        return $this->render('workflow/index.html.twig', [
            'workflows' => $workflowRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_workflow_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $workflow = new Workflow();
        $form = $this->createForm(WorkflowType::class, $workflow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workflow->setCreatedAt(new \DateTimeImmutable());
            $workflow->setInitialPlace('draft');
            
            $em->persist($workflow);
            $em->flush();

            $this->addFlash('success', 'Workflow créé avec succès !');
            return $this->redirectToRoute('app_workflow_index');
        }

        return $this->render('workflow/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_workflow_show', methods: ['GET'])]
    public function show(string $id, WorkflowRepository $workflowRepository): Response
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        return $this->render('workflow/show.html.twig', [
            'workflow' => $workflow,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_workflow_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($id);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $form = $this->createForm(WorkflowType::class, $workflow);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $workflow->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();

            $this->addFlash('success', 'Workflow modifié avec succès !');
            return $this->redirectToRoute('app_workflow_index');
        }

        return $this->render('workflow/edit.html.twig', [
            'workflow' => $workflow,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_workflow_delete', methods: ['POST'])]
    public function delete(string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($id);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        if ($this->isCsrfTokenValid('delete'.$workflow->getId(), $request->request->get('_token'))) {
            $em->remove($workflow);
            $em->flush();
            $this->addFlash('success', 'Workflow supprimé !');
        }

        return $this->redirectToRoute('app_workflow_index');
    }
}