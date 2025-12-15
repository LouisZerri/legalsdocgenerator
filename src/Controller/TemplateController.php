<?php

namespace App\Controller;

use App\Entity\Template;
use App\Entity\User;
use App\Repository\TemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/templates')]
#[IsGranted('ROLE_USER')]
class TemplateController extends AbstractController
{
    #[Route('', name: 'app_template_index')]
    public function index(TemplateRepository $templateRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $templates = $templateRepository->findAccessibleByUser($user);

        return $this->render('template/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/new', name: 'app_template_new')]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            $template = new Template();
            $template->setName($request->request->get('name'));
            $template->setDescription($request->request->get('description'));
            $template->setBodyMarkdown($request->request->get('bodyMarkdown'));
            $template->setVisibility($request->request->get('visibility', 'private'));
            $template->setCreatedBy($user);
            $template->setCreatedAt(new \DateTimeImmutable());

            if ($template->getVisibility() === 'private') {
                $template->setOrganization($user->getOrganization());
            }

            $variables = $this->extractVariables($template->getBodyMarkdown());
            $template->setVariablesJson($variables);

            $em->persist($template);
            $em->flush();

            $this->addFlash('success', 'Template créé avec succès.');
            return $this->redirectToRoute('app_template_show', ['id' => $template->getId()]);
        }

        return $this->render('template/new.html.twig');
    }

    #[Route('/{id}', name: 'app_template_show', requirements: ['id' => '\d+'])]
    public function show(Template $template): Response
    {
        $this->denyAccessUnlessGranted('view', $template);

        return $this->render('template/show.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_template_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Template $template, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('edit', $template);

        if ($request->isMethod('POST')) {
            $template->setName($request->request->get('name'));
            $template->setDescription($request->request->get('description'));
            $template->setBodyMarkdown($request->request->get('bodyMarkdown'));
            $template->setVisibility($request->request->get('visibility', 'private'));
            $template->setUpdatedAt(new \DateTimeImmutable());

            if ($template->getVisibility() === 'global') {
                $template->setOrganization(null);
            } elseif ($template->getVisibility() === 'private' && !$template->getOrganization()) {
                $template->setOrganization($user->getOrganization());
            }

            $variables = $this->extractVariables($template->getBodyMarkdown());
            $template->setVariablesJson($variables);

            $template->setVersion($template->getVersion() + 1);

            $em->flush();

            $this->addFlash('success', 'Template mis à jour.');
            return $this->redirectToRoute('app_template_show', ['id' => $template->getId()]);
        }

        return $this->render('template/edit.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_template_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function delete(Template $template, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('edit', $template);

        if ($this->isCsrfTokenValid('delete' . $template->getId(), $request->request->get('_token'))) {
            $em->remove($template);
            $em->flush();
            $this->addFlash('success', 'Template supprimé.');
        }

        return $this->redirectToRoute('app_template_index');
    }

    private function extractVariables(string $markdown): array
    {
        // Capture {{nom}}, {{nom:type}} ou {{nom:select:opt1,opt2,opt3}}
        preg_match_all('/\{\{(\w+)(?::(\w+))?(?::([^}]+))?\}\}/', $markdown, $matches, PREG_SET_ORDER);

        $variables = [];
        $seen = [];

        foreach ($matches as $match) {
            $varName = $match[1];

            // Éviter les doublons
            if (in_array($varName, $seen)) {
                continue;
            }
            $seen[] = $varName;

            // Type explicite ou déduit du nom
            $type = $match[2] ?? $this->guessType($varName);

            $variable = [
                'name' => $varName,
                'label' => ucfirst(str_replace('_', ' ', $varName)),
                'type' => $type,
                'required' => true,
            ];

            // Options pour le select
            if ($type === 'select' && isset($match[3])) {
                $variable['options'] = array_map('trim', explode(',', $match[3]));
            }

            $variables[] = $variable;
        }

        return $variables;
    }

    private function guessType(string $varName): string
    {
        // Deviner le type selon le nom de la variable
        if (str_contains($varName, 'date')) {
            return 'date';
        }
        if (str_contains($varName, 'email')) {
            return 'email';
        }
        if (str_contains($varName, 'montant') || str_contains($varName, 'capital') || str_contains($varName, 'prix') || str_contains($varName, 'tarif') || str_contains($varName, 'duree') || str_contains($varName, 'nombre') || str_contains($varName, 'quantite')) {
            return 'number';
        }
        if (str_contains($varName, 'adresse') || str_contains($varName, 'description') || str_contains($varName, 'objet') || str_contains($varName, 'commentaire')) {
            return 'textarea';
        }

        return 'text';
    }
}
