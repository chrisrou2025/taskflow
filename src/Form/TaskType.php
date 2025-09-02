<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User $user */
        $user = $options['user'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de la tâche',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Donnez un titre à votre tâche',
                    'maxlength' => 255
                ],
                'help' => 'Le titre doit être clair et descriptif (3 à 255 caractères)'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Décrivez en détail cette tâche...',
                    'rows' => 4,
                    'maxlength' => 2000
                ],
                'help' => 'Description détaillée de la tâche (maximum 2000 caractères)'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => Task::getStatusChoices(),
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Statut actuel de la tâche'
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => Task::getPriorityChoices(),
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Niveau de priorité de la tâche'
            ])
            ->add('dueDate', DateTimeType::class, [
                'label' => 'Date d\'échéance',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                    'type' => 'datetime-local'
                ],
                'help' => 'Date et heure limite pour cette tâche (optionnel)'
            ])
            ->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => 'title',
                'query_builder' => function ($repository) use ($user) {
                    return $repository->createQueryBuilder('p')
                        ->where('p.owner = :user')
                        ->setParameter('user', $user)
                        ->orderBy('p.title', 'ASC');
                },
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Projet auquel appartient cette tâche',
                'placeholder' => 'Sélectionnez un projet...'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);

        $resolver->setRequired(['user']);
        $resolver->setAllowedTypes('user', [User::class]);
    }
}
