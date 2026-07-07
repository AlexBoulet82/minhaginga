<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class UserController extends AbstractController
{
    #[Route('/api/profile', name: 'app_api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'accountType' => $user->getAccountType(),
        ];

        if ($user->getAccountType() === 'academie') {
            $data['nom_ecole'] = $user->getFirstname();
            $data['description'] = $user->getDescription();
            
            $teachers = [];
            foreach ($user->getTeachers() as $teacher) {
                $teachers[] = [
                    'id' => $teacher->getId(),
                    'apelido' => $teacher->getApelido(),
                ];
            }
            $data['profs_associes'] = $teachers;
        } else {
            $data['firstname'] = $user->getFirstname();
            $data['lastname'] = $user->getLastname();
            $data['apelido'] = $user->getApelido();
            $data['graduacao'] = $user->getGraduaçao();
        }

        return $this->json($data);
    }
}