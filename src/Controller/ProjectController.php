<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
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
        $projects = $projectRepository->findProjectsByUserWithCollaborations($this->getUser());

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
        if ($project->getOwner() !== $this->getUser() && !$project->hasCollaborator($this->getUser())) {
            $this->addFlash('error', 'Vous n\'avez pas les permissions pour accéder à ce projet.');
            return $this->redirectToRoute('project_index');
        }

        $tasks = $taskRepository->findByProjectWithFilters($project);

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'tasks' => $tasks,
        ]);
    }

    #[Route('/{id}/edit', name: 'project_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le propriétaire du projet peut le modifier.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $project->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'Le projet "' . $project->getTitle() . '" a été mis à jour avec succès !');

            return $this->redirectToRoute('project_index');
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'project_delete', methods: ['POST'])]
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le propriétaire du projet peut le supprimer.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $project->getId(), $request->request->get('_token'))) {
            $title = $project->getTitle();
            $entityManager->remove($project);
            $entityManager->flush();
            $this->addFlash('success', "Le projet \"$title\" a été supprimé.");
        }

        return $this->redirectToRoute('project_index');
    }

    #[Route('/{id}/duplicate', name: 'project_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getOwner() !== $this->getUser() && !$project->hasCollaborator($this->getUser())) {
            $this->addFlash('error', 'Vous ne pouvez pas dupliquer ce projet.');
            return $this->redirectToRoute('project_index');
        }

        if ($this->isCsrfTokenValid('duplicate' . $project->getId(), $request->request->get('_token'))) {
            $newProject = new Project();
            $newProject->setTitle($project->getTitle() . ' (Copie)');
            $newProject->setDescription($project->getDescription());
            $newProject->setOwner($this->getUser());

            foreach ($project->getTasks() as $task) {
                $newTask = clone $task;
                $newTask->setProject($newProject);
                $newTask->setStatus(Task::STATUS_TODO);
                $newTask->setCompletedAt(null);
                $newTask->setCreatedAt(new \DateTimeImmutable());
                $newTask->setUpdatedAt(null);
                $newProject->addTask($newTask);
            }

            $entityManager->persist($newProject);
            $entityManager->flush();

            $this->addFlash('success', 'Le projet a été dupliqué avec succès !');
            return $this->redirectToRoute('project_show', ['id' => $newProject->getId()]);
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }

    #[Route('/{id}/archive', name: 'project_archive', methods: ['POST'])]
    public function archive(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($project->getOwner() !== $this->getUser()) {
            $this->addFlash('error', 'Seul le propriétaire du projet peut l\'archiver.');
            return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
        }

        if ($this->isCsrfTokenValid('archive' . $project->getId(), $request->request->get('_token'))) {
            $project->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Projet archivé avec succès.');
        }

        return $this->redirectToRoute('project_show', ['id' => $project->getId()]);
    }
}
