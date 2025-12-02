<?php

namespace App\Form;

use App\Entity\Place;
use App\Entity\Transition;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransitionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $workflow = $options['workflow'];

        $builder
            ->add('name', TextType::class, [
                'label' => 'Identifiant technique',
                'attr' => ['placeholder' => 'ex: valider']
            ])
            ->add('label', TextType::class, [
                'label' => 'Nom affiché',
                'attr' => ['placeholder' => 'ex: Valider']
            ])
            ->add('fromPlace', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'label',
                'label' => 'Depuis l\'étape',
                'choices' => $workflow->getPlaces(),
            ])
            ->add('toPlace', EntityType::class, [
                'class' => Place::class,
                'choice_label' => 'label',
                'label' => 'Vers l\'étape',
                'choices' => $workflow->getPlaces(),
            ])
            ->add('condition', TextareaType::class, [
                'label' => 'Condition (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 2,
                    'placeholder' => 'ex: data["montant"] > 100'
                ],
                'help' => 'Expression qui doit être vraie pour autoriser la transition. Variables disponibles : subject, data'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Transition::class,
        ]);
        $resolver->setRequired('workflow');
    }
}