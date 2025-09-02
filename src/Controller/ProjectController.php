<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
#[IsGranted('ROLE_USER')]
class ProjectController extends AbstractController
{
    #[Route('/', name: 'project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        // Récupération des projets de l'utilisateur connecté
        $projects = $projectRepository->findBy(
            ['owner' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    #[Route('/new', name: 'project_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Attribution du projet à l'utilisateur connecté
            $project->setOwner($this->getUser());

            $entityManager->persist($project);
            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été créé avec succès !');

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_show', methods: ['GET'])]
    public function show(Project $project, TaskRepository $taskRepository): Response
    {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        // Récupération des statistiques du projet
        $statistics = $taskRepository->getProjectStatistics($project);

        // Récupération des tâches récentes du projet
        $recentTasks = $taskRepository->findBy(
            ['project' => $project],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'statistics' => $statistics,
            'recent_tasks' => $recentTasks,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mise à jour de la date de modification
            $project->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été modifié avec succès !');

            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_DELETE', $project);

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $projectTitle = $project->getTitle();
            $entityManager->remove($project);
            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $projectTitle . '" et toutes ses tâches ont été supprimés.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La suppression a échoué.');
        }

        return $this->redirectToRoute('project_index');
    }

    #[Route('/{id}/duplicate', name: 'project_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_VIEW', $project);

        if ($this->isCsrfTokenValid('duplicate' . $project->getId(), $request->request->get('_token'))) {
            // Création d'une copie du projet
            $newProject = new Project();
            $newProject->setTitle($project->getTitle() . ' (Copie)');
            $newProject->setDescription($project->getDescription());
            $newProject->setOwner($this->getUser());

            $entityManager->persist($newProject);
            $entityManager->flush();

            $this->addFlash('success', 'Le projet a été dupliqué avec succès !');

            return $this->redirectToRoute('project_show', ['id' => $newProject->getId()]);
        } else {
            $this->addFlash('error', 'Token CSRF invalide. La duplication a échoué.');
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/archive', name: 'project_archive', methods: ['POST'])]
    public function archive(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        // Vérification que l'utilisateur est propriétaire du projet
        $this->denyAccessUnlessGranted('PROJECT_EDIT', $project);

        if ($this->isCsrfTokenValid('archive' . $project->getId(), $request->request->get('_token'))) {
            // Pour l'instant, on utilise la date de mise à jour pour marquer l'archivage
            // Dans une version future, on pourrait ajouter un champ "archived" à l'entité
            $project->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été archivé.');
        }

        return $this->redirectToRoute('project_index');
    }
}
