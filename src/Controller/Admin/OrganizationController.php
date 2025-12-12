<?php

namespace App\Controller\Admin;

use App\Entity\Organization;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/organizations')]
#[IsGranted('ROLE_SUPER_ADMIN')]
class OrganizationController extends AbstractController
{
    #[Route('', name: 'app_admin_organization_index')]
    public function index(OrganizationRepository $organizationRepository): Response
    {
        $organizations = $organizationRepository->findBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/organization/index.html.twig', [
            'organizations' => $organizations,
        ]);
    }

    #[Route('/new', name: 'app_admin_organization_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $organization = new Organization();
            $organization->setName($request->request->get('name'));
            $organization->setSettings([
                'plan' => $request->request->get('plan', 'free'),
            ]);
            $organization->setCreatedAt(new \DateTimeImmutable());
            
            $em->persist($organization);
            $em->flush();
            
            $this->addFlash('success', 'Organisation créée avec succès.');
            return $this->redirectToRoute('app_admin_organization_index');
        }
        
        return $this->render('admin/organization/new.html.twig');
    }

    #[Route('/{id}', name: 'app_admin_organization_show')]
    public function show(Organization $organization): Response
    {
        return $this->render('admin/organization/show.html.twig', [
            'organization' => $organization,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_organization_edit')]
    public function edit(Organization $organization, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $organization->setName($request->request->get('name'));
            $organization->setSettings([
                'plan' => $request->request->get('plan', 'free'),
            ]);
            
            $em->flush();
            
            $this->addFlash('success', 'Organisation mise à jour.');
            return $this->redirectToRoute('app_admin_organization_show', ['id' => $organization->getId()]);
        }
        
        return $this->render('admin/organization/edit.html.twig', [
            'organization' => $organization,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_organization_delete', methods: ['POST'])]
    public function delete(Organization $organization, Request $request, EntityManagerInterface $em): Response
    {
        if ($organization->getUsers()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer une organisation avec des utilisateurs.');
            return $this->redirectToRoute('app_admin_organization_index');
        }
        
        if ($this->isCsrfTokenValid('delete' . $organization->getId(), $request->request->get('_token'))) {
            $em->remove($organization);
            $em->flush();
            $this->addFlash('success', 'Organisation supprimée.');
        }
        
        return $this->redirectToRoute('app_admin_organization_index');
    }
}