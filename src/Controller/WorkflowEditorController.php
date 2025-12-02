<?php

namespace App\Controller;

use App\Entity\Place;
use App\Entity\Transition;
use App\Repository\WorkflowRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workflow/{id}/editor')]
class WorkflowEditorController extends AbstractController
{
    #[Route('', name: 'app_workflow_editor', methods: ['GET'])]
    public function editor(string $id, WorkflowRepository $workflowRepository): Response
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            throw $this->createNotFoundException('Workflow non trouvé');
        }

        return $this->render('workflow/editor.html.twig', [
            'workflow' => $workflow,
        ]);
    }

    #[Route('/save', name: 'app_workflow_editor_save', methods: ['POST'])]
    public function save(string $id, WorkflowRepository $workflowRepository, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $workflow = $workflowRepository->find($id);

        if (!$workflow) {
            return new JsonResponse(['error' => 'Workflow non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);

        // Supprimer les anciennes places et transitions
        foreach ($workflow->getTransitions() as $transition) {
            $em->remove($transition);
        }
        foreach ($workflow->getPlaces() as $place) {
            $em->remove($place);
        }
        $em->flush();

        // Créer les nouvelles places
        $placeEntities = [];
        foreach ($data['nodes'] as $node) {
            $place = new Place();
            $place->setName($node['name']);
            $place->setLabel($node['label']);
            $place->setWorkflow($workflow);
            $em->persist($place);
            $placeEntities[$node['id']] = $place;
        }
        $em->flush();

        // Créer les nouvelles transitions
        foreach ($data['connections'] as $conn) {
            $fromPlace = $placeEntities[$conn['from']] ?? null;
            $toPlace = $placeEntities[$conn['to']] ?? null;

            if ($fromPlace && $toPlace) {
                $transition = new Transition();
                $transition->setName($conn['name'] ?? 'transition_' . uniqid());
                $transition->setLabel($conn['label'] ?? $fromPlace->getLabel() . ' → ' . $toPlace->getLabel());
                $transition->setFromPlace($fromPlace);
                $transition->setToPlace($toPlace);
                $transition->setWorkflow($workflow);
                $em->persist($transition);
            }
        }

        // Définir l'étape initiale
        if (!empty($data['initialPlace']) && isset($placeEntities[$data['initialPlace']])) {
            $workflow->setInitialPlace($placeEntities[$data['initialPlace']]->getName());
        }

        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}