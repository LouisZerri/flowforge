<?php

namespace App\Controller;

use App\Entity\Place;
use App\Form\PlaceType;
use App\Repository\WorkflowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workflow/{workflowId}/place')]
final class PlaceController extends AbstractController
{
    #[Route('/new', name: 'app_place_new', methods: ['GET', 'POST'])]
    public function new(string $workflowId, Request $request, EntityManagerInterface $em, WorkflowRepository $workflowRepository): Response
    {
        $workflow = $workflowRepository->find($workflowId);

        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $place = new Place();
        $form = $this->createForm(PlaceType::class, $place);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $place->setWorkflow($workflow);
            $em->persist($place);
            $em->flush();

            $this->addFlash('success', 'Étape créée avec succès');
            return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
        }

        return $this->render('place/new.html.twig', [
            'form' => $form,
            'workflow' => $workflow,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_place_delete', methods: ['POST'])]
    public function delete(string $workflowId, string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): Response
    {
        $workflow = $workflowRepository->find($workflowId);
        
        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        $place = $em->getRepository(Place::class)->find($id);

        if ($place && $this->isCsrfTokenValid('delete'.$place->getId(), $request->request->get('_token'))) {
            $em->remove($place);
            $em->flush();
            $this->addFlash('success', 'Étape supprimée !');
        }

        return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
    }
}
