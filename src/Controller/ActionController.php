<?php

namespace App\Controller;

use App\Entity\Action;
use App\Repository\TransitionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/workflow/{workflowId}/transition/{transitionId}/action')]
final class ActionController extends AbstractController
{
    #[Route('/new', name: 'app_action_new', methods: ['GET', 'POST'])]
    public function new(string $workflowId, string $transitionId, TransitionRepository $transitionRepository, Request $request, EntityManagerInterface $em): Response
    {
        $transition = $transitionRepository->find($transitionId);
        
        if (!$transition) {
            throw $this->createNotFoundException('Transition non trouvée');
        }

        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $type = $request->request->get('type');
            $configJson = $request->request->get('config');

            if ($name && $type) {
                $action = new Action();
                $action->setName($name);
                $action->setType($type);
                $action->setTransition($transition);

                if ($configJson) {
                    $config = json_decode($configJson, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $action->setConfig($config);
                    }
                }

                $em->persist($action);
                $em->flush();

                $this->addFlash('success', 'Action ajoutée !');
                return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
            }

            $this->addFlash('error', 'Nom et type sont obligatoires.');
        }

        return $this->render('action/new.html.twig', [
            'workflowId' => $workflowId,
            'transition' => $transition,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_action_delete', methods: ['POST'])]
    public function delete(string $workflowId, string $id, Request $request, EntityManagerInterface $em): Response
    {
        $action = $em->getRepository(Action::class)->find($id);

        if ($action && $this->isCsrfTokenValid('delete'.$action->getId(), $request->request->get('_token'))) {
            $em->remove($action);
            $em->flush();
            $this->addFlash('success', 'Action supprimée !');
        }

        return $this->redirectToRoute('app_workflow_show', ['id' => $workflowId]);
    }
}