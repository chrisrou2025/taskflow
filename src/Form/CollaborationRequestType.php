<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollaborationRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $availableUsers = $options['available_users'] ?? [];

        $builder
            ->add('invitedUser', EntityType::class, [
                'class' => User::class,
                'choices' => $availableUsers,
                'choice_label' => 'fullName', // Seulement le nom complet
                'choice_value' => 'id',
                'placeholder' => count($availableUsers) > 0
                    ? '-- Sélectionner un utilisateur --'
                    : 'Aucun utilisateur disponible',
                'label' => 'Utilisateur à inviter',
                'attr' => [
                    'class' => 'form-select'
                ],
                'required' => true,
                'disabled' => count($availableUsers) === 0,
                'help' => count($availableUsers) === 0
                    ? 'Tous les utilisateurs disponibles ont déjà été invités ou collaborent déjà sur ce projet.'
                    : 'Sélectionnez l\'utilisateur que vous souhaitez inviter à collaborer.'
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message d\'invitation (optionnel)',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Ajoutez un message personnalisé pour expliquer pourquoi vous souhaitez collaborer avec cette personne...',
                    'maxlength' => 500
                ],
                'help' => 'Un message personnalisé augmente les chances d\'acceptation de votre invitation (max. 500 caractères).'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'available_users' => [],
        ]);

        $resolver->setAllowedTypes('available_users', 'array');
    }
}
