<?php

namespace App\Service;

use App\Entity\Workflow as WorkflowEntity;
use App\Entity\WorkflowSubject;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\MethodMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class DynamicWorkflowBuilder
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public function build(WorkflowEntity $workflowEntity): Workflow
    {
        $builder = new DefinitionBuilder();

        foreach ($workflowEntity->getPlaces() as $place) {
            $builder->addPlace($place->getName());
        }

        $builder->setInitialPlaces([$workflowEntity->getInitialPlace()]);

        foreach ($workflowEntity->getTransitions() as $transition) {
            $builder->addTransition(new Transition(
                $transition->getName(),
                $transition->getFromPlace()->getName(),
                $transition->getToPlace()->getName()
            ));
        }

        $definition = $builder->build();

        $markingStore = new MethodMarkingStore(true, 'currentPlace');

        return new Workflow($definition, $markingStore, name: $workflowEntity->getName());
    }

    public function canTransition(WorkflowEntity $workflowEntity, WorkflowSubject $subject, string $transitionName): bool
    {
        $workflow = $this->build($workflowEntity);

        if (!$workflow->can($subject, $transitionName)) {
            return false;
        }

        // Vérifier la condition
        $transitionEntity = null;
        foreach ($workflowEntity->getTransitions() as $t) {
            if ($t->getName() === $transitionName) {
                $transitionEntity = $t;
                break;
            }
        }

        if ($transitionEntity && $transitionEntity->getCondition()) {
            try {
                return (bool) $this->expressionLanguage->evaluate(
                    $transitionEntity->getCondition(),
                    [
                        'subject' => $subject,
                        'data' => $subject->getData() ?? [],
                    ]
                );
            } catch (\Exception $e) {
                return false;
            }
        }

        return true;
    }
}