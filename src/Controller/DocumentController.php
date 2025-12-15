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
            // Récupérer les données du formulaire
            $rawData = [];
            foreach ($template->getVariablesJson() ?? [] as $variable) {
                $rawData[$variable['name']] = $request->request->get($variable['name'], '');
            }

            // Valider les données
            $result = $this->validateAndFormatData($template->getVariablesJson() ?? [], $rawData);

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('document/new.html.twig', [
                    'template' => $template,
                    'oldData' => $rawData,
                ]);
            }

            $document = new Document();
            $document->setTemplate($template);
            $document->setCreatedBy($user);
            $document->setOrganization($user->getOrganization());
            $document->setCreatedAt(new \DateTimeImmutable());
            $document->setDataJson($result['data']);

            // Générer le contenu
            $generatedContent = $this->generateContent($template, $result['data']);
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
            'oldData' => [],
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

            // Récupérer les données du formulaire
            $rawData = [];
            foreach ($template->getVariablesJson() ?? [] as $variable) {
                $rawData[$variable['name']] = $request->request->get($variable['name'], '');
            }

            // Valider les données
            $result = $this->validateAndFormatData($template->getVariablesJson() ?? [], $rawData);

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('document/edit.html.twig', [
                    'document' => $document,
                    'oldData' => $rawData,
                ]);
            }

            $document->setDataJson($result['data']);

            $generatedContent = $this->generateContent($template, $result['data']);
            $document->setGeneratedContent($generatedContent);
            $document->setUpdatedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'Document mis à jour.');
            return $this->redirectToRoute('app_document_show', ['id' => $document->getId()]);
        }

        return $this->render('document/edit.html.twig', [
            'document' => $document,
            'oldData' => $document->getDataJson() ?? [],
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

    private function validateAndFormatData(array $variables, array $data): array
    {
        $errors = [];
        $formattedData = [];

        foreach ($variables as $variable) {
            $name = $variable['name'];
            $type = $variable['type'];
            $required = $variable['required'] ?? false;
            $value = trim($data[$name] ?? '');

            // Champ requis
            if ($required && empty($value)) {
                $errors[] = sprintf('Le champ "%s" est obligatoire.', $variable['label'] ?? $name);
                continue;
            }

            if (empty($value)) {
                $formattedData[$name] = '';
                continue;
            }

            // Validation selon le type
            switch ($type) {
                case 'date':
                    // Vérifier le format YYYY-MM-DD
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        $errors[] = sprintf('Le champ "%s" doit être une date valide.', $variable['label'] ?? $name);
                        break;
                    }
                    // Vérifier que la date est valide
                    $parts = explode('-', $value);
                    if (!checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                        $errors[] = sprintf('Le champ "%s" contient une date invalide.', $variable['label'] ?? $name);
                        break;
                    }
                    // Vérifier l'année raisonnable (1900-2100)
                    $year = (int)$parts[0];
                    if ($year < 1900 || $year > 2100) {
                        $errors[] = sprintf('Le champ "%s" doit avoir une année entre 1900 et 2100.', $variable['label'] ?? $name);
                        break;
                    }
                    $formattedData[$name] = $value;
                    break;

                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = sprintf('Le champ "%s" doit être un email valide.', $variable['label'] ?? $name);
                        break;
                    }
                    $formattedData[$name] = $value;
                    break;

                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = sprintf('Le champ "%s" doit être un nombre.', $variable['label'] ?? $name);
                        break;
                    }
                    $formattedData[$name] = $value;
                    break;

                case 'select':
                    $options = $variable['options'] ?? [];
                    if (!empty($options) && !in_array($value, $options)) {
                        $errors[] = sprintf('Le champ "%s" contient une valeur non autorisée.', $variable['label'] ?? $name);
                        break;
                    }
                    $formattedData[$name] = $value;
                    break;

                default:
                    // text, textarea - pas de validation spécifique
                    $formattedData[$name] = $value;
                    break;
            }
        }

        return ['data' => $formattedData, 'errors' => $errors];
    }

    private function generateContent(Template $template, array $data): string
    {
        $content = $template->getBodyMarkdown();

        // Formatter les dates en français
        $formatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE
        );

        foreach ($data as $key => $value) {
            // Détecter si c'est une date (format YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $date = new \DateTime($value);
                $value = $formatter->format($date);
            }

            // Remplacer {{key}}, {{key:type}} et {{key:select:options}}
            $content = preg_replace('/\{\{' . preg_quote($key, '/') . '(:[^}]+)?\}\}/', $value, $content);
        }

        return $content;
    }
}
