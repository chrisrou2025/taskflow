<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:promote',
    description: 'Promouvoir un utilisateur en administrateur ou gérer ses rôles',
)]
class PromoteUserCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email de l\'utilisateur')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Rôle à ajouter (ROLE_ADMIN par défaut)', 'ROLE_ADMIN')
            ->addOption('remove', null, InputOption::VALUE_NONE, 'Retirer le rôle au lieu de l\'ajouter')
            ->addOption('list', 'l', InputOption::VALUE_NONE, 'Afficher tous les utilisateurs avec leurs rôles');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Option pour lister tous les utilisateurs
        if ($input->getOption('list')) {
            return $this->listUsers($io);
        }

        $email = $input->getArgument('email');
        $role = $input->getOption('role');
        $remove = $input->getOption('remove');

        // Vérifier que le rôle est valide
        if (!str_starts_with($role, 'ROLE_')) {
            $io->error('Le rôle doit commencer par "ROLE_"');
            return Command::FAILURE;
        }

        // Trouver l'utilisateur
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user) {
            $io->error("Aucun utilisateur trouvé avec l'email: $email");
            return Command::FAILURE;
        }

        $currentRoles = $user->getRoles();

        if ($remove) {
            // Retirer le rôle
            if (!in_array($role, $currentRoles)) {
                $io->warning("L'utilisateur {$user->getFullName()} n'a pas le rôle $role");
                return Command::SUCCESS;
            }

            $newRoles = array_filter($currentRoles, fn($r) => $r !== $role);
            $user->setRoles(array_values($newRoles));
            
            $this->entityManager->flush();
            
            $io->success("Rôle $role retiré de l'utilisateur {$user->getFullName()}");
        } else {
            // Ajouter le rôle
            if (in_array($role, $currentRoles)) {
                $io->warning("L'utilisateur {$user->getFullName()} a déjà le rôle $role");
                return Command::SUCCESS;
            }

            $currentRoles[] = $role;
            $user->setRoles($currentRoles);
            
            $this->entityManager->flush();
            
            $io->success("Rôle $role ajouté à l'utilisateur {$user->getFullName()}");
        }

        // Afficher les rôles actuels
        $io->table(
            ['Information', 'Valeur'],
            [
                ['Nom complet', $user->getFullName()],
                ['Email', $user->getEmail()],
                ['Rôles actuels', implode(', ', $user->getRoles())],
            ]
        );

        return Command::SUCCESS;
    }

    private function listUsers(SymfonyStyle $io): int
    {
        $users = $this->userRepository->findAll();

        if (empty($users)) {
            $io->info('Aucun utilisateur trouvé');
            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($users as $user) {
            $roles = array_filter($user->getRoles(), fn($role) => $role !== 'ROLE_USER');
            $rows[] = [
                $user->getId(),
                $user->getFullName(),
                $user->getEmail(),
                empty($roles) ? 'Utilisateur' : implode(', ', $roles),
                $user->getCreatedAt()->format('d/m/Y')
            ];
        }

        $io->table(
            ['ID', 'Nom complet', 'Email', 'Rôles spéciaux', 'Créé le'],
            $rows
        );

        return Command::SUCCESS;
    }
}