<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\Template;
use App\Entity\User;
use App\Message\GeneratePdfMessage;
use App\Repository\DocumentRepository;
use App\Service\PdfGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/documents')]
#[IsGranted('ROLE_USER')]
class DocumentController extends AbstractController
{
    #[Route('', name: 'app_document_index')]
    public function index(Request $request, DocumentRepository $documentRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $status = $request->query->get('status');

        $documents = $documentRepository->findByUser($user, $status);

        return $this->render('document/index.html.twig', [
            'documents' => $documents,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/new/{id}', name: 'app_document_new', requirements: ['id' => '\d+'])]
    public function new(Template $template, Request $request, EntityManagerInterface $em, MessageBusInterface $messageBus): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->denyAccessUnlessGranted('view', $template);

        if ($request->isMethod('POST')) {
            $document = new Document();
            $document->setTemplate($template);
            $document->setCreatedBy($user);
            $document->setOrganization($user->getOrganization());
            $document->setCreatedAt(new \DateTimeImmutable());

            // Récupérer les données du formulaire
            $data = [];
            foreach ($template->getVariablesJson() ?? [] as $variable) {
                $data[$variable['name']] = $request->request->get($variable['name'], '');
            }
            $document->setDataJson($data);

            // Générer le contenu
            $generatedContent = $this->generateContent($template, $data);
            $document->setGeneratedContent($generatedContent);

            $em->persist($document);
            $em->flush();

            // Générer le PDF en arrière-plan
            $messageBus->dispatch(new GeneratePdfMessage($document->getId()));

            $this->addFlash('success', 'Document créé avec succès. Le PDF est en cours de génération.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/new.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}', name: 'app_document_show', requirements: ['id' => '\d+'])]
    public function show(Document $document): Response
    {
        $this->denyAccessUnlessGranted('view', $document);

        return $this->render('document/show.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_document_edit', requirements: ['id' => '\d+'])]
    public function edit(Document $document, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('edit', $document);

        if ($document->getStatus() !== Document::STATUS_DRAFT) {
            $this->addFlash('error', 'Seuls les brouillons peuvent être modifiés.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        if ($request->isMethod('POST')) {
            $template = $document->getTemplate();

            $data = [];
            foreach ($template->getVariablesJson() ?? [] as $variable) {
                $data[$variable['name']] = $request->request->get($variable['name'], '');
            }
            $document->setDataJson($data);

            $generatedContent = $this->generateContent($template, $data);
            $document->setGeneratedContent($generatedContent);
            $document->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'Document mis à jour.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
        ]);
    }

    #[Route('/{id}/status/{status}', name: 'app_document_status', requirements: ['id' => '\d+'])]
    public function changeStatus(Document $document, string $status, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('edit', $document);

        $allowedTransitions = [
            Document::STATUS_DRAFT => [Document::STATUS_REVIEW],
            Document::STATUS_REVIEW => [Document::STATUS_DRAFT, Document::STATUS_APPROVED],
            Document::STATUS_APPROVED => [Document::STATUS_REVIEW, Document::STATUS_SIGNED],
            Document::STATUS_SIGNED => [Document::STATUS_ARCHIVED],
            Document::STATUS_ARCHIVED => [],
        ];

        if (!in_array($status, $allowedTransitions[$document->getStatus()] ?? [])) {
            $this->addFlash('error', 'Transition de statut non autorisée.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        $document->setStatus($status);
        $document->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $statusLabels = [
            Document::STATUS_DRAFT => 'brouillon',
            Document::STATUS_REVIEW => 'en revue',
            Document::STATUS_APPROVED => 'approuvé',
            Document::STATUS_SIGNED => 'signé',
            Document::STATUS_ARCHIVED => 'archivé',
        ];

        $this->addFlash('success', 'Document passé en ' . $statusLabels[$status] . '.');
        return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
    }

    #[Route('/{id}/delete', name: 'app_document_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Document $document, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('edit', $document);

        if ($document->getStatus() !== Document::STATUS_DRAFT) {
            $this->addFlash('error', 'Seuls les brouillons peuvent être supprimés.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $document->getId(), $request->request->get('_token'))) {
            $em->remove($document);
            $em->flush();
            $this->addFlash('success', 'Document supprimé.');
        }

        return $this->redirectToRoute('app_document_index');
    }

    #[Route('/{id}/pdf', name: 'app_document_pdf', requirements: ['id' => '\d+'])]
    public function downloadPdf(Document $document, PdfGenerator $pdfGenerator): Response
    {
        $this->denyAccessUnlessGranted('view', $document);

        $pdfContent = $pdfGenerator->stream($document);

        $response = new Response($pdfContent);
        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            sprintf('document_%d.pdf', $document->getId())
        );

        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    #[Route('/{id}/pdf/view', name: 'app_document_pdf_view', requirements: ['id' => '\d+'])]
    public function viewPdf(Document $document, PdfGenerator $pdfGenerator): Response
    {
        $this->denyAccessUnlessGranted('view', $document);

        $pdfContent = $pdfGenerator->stream($document);

        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="document_' . $document->getId() . '.pdf"');

        return $response;
    }

    private function generateContent(Template $template, array $data): string
    {
        $content = $template->getBodyMarkdown();

        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }
}
