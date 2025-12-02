<?php

namespace App\Controller;

use App\Entity\Transition;
use App\Form\TransitionType;
use App\Repository\WorkflowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workflow/{workflowId}/transition')]
final class TransitionController extends AbstractController
{
    #[Route('/new', name: 'app_transition_new', methods: ['GET', 'POST'])]
    public function new(string $workflowId, Request $request, EntityManagerInterface $em, WorkflowRepository $workflowRepository): Response
    {
        $workflow = $workflowRepository->find($workflowId);

        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $transition = new Transition();
        $form = $this->createForm(TransitionType::class, $transition, ['workflow' => $workflow]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $transition->setWorkflow($workflow);
            $em->persist($transition);
            $em->flush();

            $this->addFlash('success', 'Transition créée avec succès');
            return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
        }

        return $this->render('transition/new.html.twig', [
            'form' => $form,
            'workflow' => $workflow,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_transition_delete', methods: ['POST'])]
    public function delete(string $workflowId, string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($workflowId);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $transition = $em->getRepository(Transition::class)->find($id);

        if ($transition && $this->isCsrfTokenValid('delete'.$transition->getId(), $request->request->get('_token'))) {
            $em->remove($transition);
            $em->flush();
            $this->addFlash('success', 'Transition supprimée !');
        }

        return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
    }
}
