<?php

namespace App\Controller;

use App\Entity\Document;
use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\TemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(DocumentRepository $documentRepository, TemplateRepository $templateRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        
        // Documents accessibles par l'utilisateur
        $documents = $documentRepository->findByUser($user);
        
        // Compteurs par statut
        $stats = [
            'total' => count($documents),
            'draft' => 0,
            'review' => 0,
            'approved' => 0,
            'signed' => 0,
            'archived' => 0,
        ];
        
        foreach ($documents as $doc) {
            $stats[$doc->getStatus()]++;
        }
        
        // Templates accessibles
        $templates = $templateRepository->findAccessibleByUser($user);
        $stats['templates'] = count($templates);
        
        // Documents récents (5 derniers)
        $recentDocuments = array_slice($documents, 0, 5);
        
        // Documents en attente d'action
        $pendingReview = array_filter($documents, fn($d) => $d->getStatus() === Document::STATUS_REVIEW);
        $pendingApproval = array_filter($documents, fn($d) => $d->getStatus() === Document::STATUS_APPROVED);
        
        // Stats pour graphique (documents créés par mois - 6 derniers mois)
        $monthlyStats = $documentRepository->getMonthlyStats($user);
        
        return $this->render('dashboard/index.html.twig', [
            'stats' => $stats,
            'recentDocuments' => $recentDocuments,
            'pendingReview' => $pendingReview,
            'pendingApproval' => $pendingApproval,
            'monthlyStats' => $monthlyStats,
        ]);
    }
}