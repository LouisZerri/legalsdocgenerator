<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\DocumentRepository;
use App\Repository\TemplateRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class SearchController extends AbstractController
{
    #[Route('/search', name: 'app_search')]
    public function index(
        Request $request,
        DocumentRepository $documentRepository,
        TemplateRepository $templateRepository,
        UserRepository $userRepository
    ): Response {
        $query = trim($request->query->get('q', ''));
        
        $results = [
            'documents' => [],
            'templates' => [],
            'users' => [],
        ];
        
        if (strlen($query) >= 2) {
            /** @var User $user */
            $user = $this->getUser();
            
            // Recherche documents
            $results['documents'] = $documentRepository->search($query, $user);
            
            // Recherche templates
            $results['templates'] = $templateRepository->search($query, $user);
            
            // Recherche utilisateurs (admin seulement)
            if ($this->isGranted('ROLE_ADMIN_ORG')) {
                $results['users'] = $userRepository->search($query, $user);
            }
        }
        
        $totalResults = count($results['documents']) + count($results['templates']) + count($results['users']);
        
        return $this->render('search/index.html.twig', [
            'query' => $query,
            'results' => $results,
            'totalResults' => $totalResults,
        ]);
    }
}