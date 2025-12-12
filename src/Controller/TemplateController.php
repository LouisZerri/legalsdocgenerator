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
        preg_match_all('/\{\{(\w+)\}\}/', $markdown, $matches);
        $variables = [];

        foreach (array_unique($matches[1]) as $varName) {
            $variables[] = [
                'name' => $varName,
                'label' => ucfirst(str_replace('_', ' ', $varName)),
                'type' => 'text',
                'required' => true,
            ];
        }

        return $variables;
    }
}
