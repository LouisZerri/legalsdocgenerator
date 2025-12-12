<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN_ORG')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_admin_user_index')]
    public function index(UserRepository $userRepository): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        // Super admin voit tous les utilisateurs
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC']);
        } else {
            // Admin org voit seulement son organisation
            $users = $userRepository->findBy(
                ['organization' => $currentUser->getOrganization()],
                ['createdAt' => 'DESC']
            );
        }
        
        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_admin_user_new')]
    public function new(
        Request $request, 
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        OrganizationRepository $organizationRepository
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        $organizations = $this->isGranted('ROLE_SUPER_ADMIN') 
            ? $organizationRepository->findAll() 
            : [$currentUser->getOrganization()];
        
        if ($request->isMethod('POST')) {
            $user = new User();
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            $user->setRoles([$request->request->get('role', 'ROLE_USER')]);
            $user->setCreatedAt(new \DateTimeImmutable());
            
            // Mot de passe
            $password = $request->request->get('password');
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            
            // Organisation
            $orgId = $request->request->get('organization');
            if ($orgId) {
                $organization = $organizationRepository->find($orgId);
                $user->setOrganization($organization);
            } elseif (!$this->isGranted('ROLE_SUPER_ADMIN')) {
                $user->setOrganization($currentUser->getOrganization());
            }
            
            $em->persist($user);
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('app_admin_user_index');
        }
        
        return $this->render('admin/user/new.html.twig', [
            'organizations' => $organizations,
            'roles' => $this->getAvailableRoles(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_user_edit')]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        OrganizationRepository $organizationRepository
    ): Response {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        // Vérifier les droits
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getOrganization() !== $currentUser->getOrganization()) {
            throw $this->createAccessDeniedException();
        }
        
        $organizations = $this->isGranted('ROLE_SUPER_ADMIN') 
            ? $organizationRepository->findAll() 
            : [$currentUser->getOrganization()];
        
        if ($request->isMethod('POST')) {
            $user->setName($request->request->get('name'));
            $user->setEmail($request->request->get('email'));
            $user->setRoles([$request->request->get('role', 'ROLE_USER')]);
            
            // Mot de passe (seulement si rempli)
            $password = $request->request->get('password');
            if ($password) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }
            
            // Organisation (super admin seulement)
            if ($this->isGranted('ROLE_SUPER_ADMIN')) {
                $orgId = $request->request->get('organization');
                if ($orgId) {
                    $organization = $organizationRepository->find($orgId);
                    $user->setOrganization($organization);
                } else {
                    $user->setOrganization(null);
                }
            }
            
            $em->flush();
            
            $this->addFlash('success', 'Utilisateur mis à jour.');
            return $this->redirectToRoute('app_admin_user_index');
        }
        
        return $this->render('admin/user/edit.html.twig', [
            'user' => $user,
            'organizations' => $organizations,
            'roles' => $this->getAvailableRoles(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        
        // Ne peut pas se supprimer soi-même
        if ($user === $currentUser) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_admin_user_index');
        }
        
        // Vérifier les droits
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && $user->getOrganization() !== $currentUser->getOrganization()) {
            throw $this->createAccessDeniedException();
        }
        
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }
        
        return $this->redirectToRoute('app_admin_user_index');
    }

    private function getAvailableRoles(): array
    {
        $roles = [
            'ROLE_USER' => 'Utilisateur',
            'ROLE_EDITOR' => 'Éditeur',
            'ROLE_VALIDATOR' => 'Validateur / Juriste',
        ];
        
        if ($this->isGranted('ROLE_SUPER_ADMIN')) {
            $roles['ROLE_ADMIN_ORG'] = 'Admin Organisation';
            $roles['ROLE_SUPER_ADMIN'] = 'Super Admin';
        } elseif ($this->isGranted('ROLE_ADMIN_ORG')) {
            $roles['ROLE_ADMIN_ORG'] = 'Admin Organisation';
        }
        
        return $roles;
    }
}