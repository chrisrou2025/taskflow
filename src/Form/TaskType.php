<?php

namespace App\Form;

use App\Entity\Task;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\UserRepository;
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
        $isCollaborator = $options['is_collaborator'];

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
                    'rows' => 4,
                    'placeholder' => 'Une description détaillée de la tâche...'
                ],
                'help' => 'Ajoutez des détails pour décrire la tâche (optionnel)'
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'À faire' => Task::STATUS_TODO,
                    'En cours' => Task::STATUS_IN_PROGRESS,
                    'Terminée' => Task::STATUS_COMPLETED,
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Statut actuel de la tâche'
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorité',
                'choices' => [
                    'Basse' => Task::PRIORITY_LOW,
                    'Moyenne' => Task::PRIORITY_MEDIUM,
                    'Haute' => Task::PRIORITY_HIGH,
                ],
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Niveau d\'importance de la tâche'
            ])
            ->add('assignee', EntityType::class, [
                'label' => 'Assignée à',
                'class' => User::class,
                'choice_label' => 'fullName',
                'required' => false,
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Attribuez cette tâche à un membre de votre équipe (optionnel)',
                'placeholder' => 'Non attribuée',
                'query_builder' => function (UserRepository $repository) use ($user) {
                    return $repository->createQueryBuilder('u')
                        ->where('u.isVerified = :verified')
                        ->setParameter('verified', true)
                        ->orderBy('u.firstName', 'ASC')
                        ->addOrderBy('u.lastName', 'ASC');
                }
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
            ]);

        if (!$isCollaborator) {
            $builder->add('project', EntityType::class, [
                'label' => 'Projet',
                'class' => Project::class,
                'choice_label' => 'title',
                'query_builder' => function ($repository) use ($user) {
                    // Utilisation de la nouvelle méthode pour inclure les projets collaboratifs
                    return $repository->findProjectsByUserQueryBuilder($user);
                },
                'attr' => [
                    'class' => 'form-select'
                ],
                'help' => 'Projet auquel appartient cette tâche',
                'placeholder' => 'Sélectionnez un projet...'
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
            'is_collaborator' => false,
        ]);

        $resolver->setRequired(['user']);
        $resolver->setAllowedTypes('user', [User::class]);
        $resolver->setAllowedTypes('is_collaborator', 'bool');
    }
}