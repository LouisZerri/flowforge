<?php

namespace App\Form;

use App\Entity\Workflow;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkflowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => ['class' => 'w-full border border-gray-300 rounded-lg px-4 py-2']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['class' => 'w-full border border-gray-300 rounded-lg px-4 py-2', 'rows' => 3]
            ])
        ;

        // Ajouter initialPlace seulement en édition (quand des places existent)
        if ($options['data']->getId() !== null) {
            $places = $options['data']->getPlaces();
            if (count($places) > 0) {
                $choices = [];
                foreach ($places as $place) {
                    $choices[$place->getLabel()] = $place->getName();
                }
                $builder->add('initialPlace', ChoiceType::class, [
                    'label' => 'Étape initiale',
                    'choices' => $choices,
                    'required' => true,
                    'attr' => ['class' => 'w-full border border-gray-300 rounded-lg px-4 py-2']
                ]);
            }
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Workflow::class,
        ]);
    }
}