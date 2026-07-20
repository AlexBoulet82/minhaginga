<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class SecurityController extends AbstractController
{
    #[Route('/api/login', name: 'app_api_login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (null === $user) {
            return $this->json([
                'message' => 'Identifiants invalides.',
            ], 401);
        }

        // Si tu utilises LexikJWTBundle pour générer le token automatiquement, 
        // ou si tu renvoies les infos de l'utilisateur connecté :
        return $this->json([
            'user'  => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }

    #[Route('/api/logout', name: 'app_api_logout', methods: ['POST'])]
    public function logout(): void
    {
        throw new \Exception('Cette méthode peut rester vide, Symfony intercepte la déconnexion automatiquement.');
    }
}