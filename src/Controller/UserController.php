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
use App\Service\FileUploader;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    #[Route('/api/user/upload-photo', name: 'app_api_user_upload_photo', methods: ['POST'])]
    public function uploadPhoto(
        Request $request,
        #[CurrentUser] ?User $user,
        FileUploader $fileUploader,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté.'], 401);
        }

        /** @var UploadedFile $file */
        $file = $request->files->get('photo');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier reçu.'], 400);
        }

        // Validation basique du type de fichier
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Format de fichier non autorisé (uniquement JPG, PNG, WEBP).'], 400);
        }

        try {
            // Upload du fichier dans le sous-dossier "photos"
            $fileName = $fileUploader->upload($file, 'photos');
            
            // On enregistre le chemin de la photo en base de données
            $user->setPhoto('/uploads/photos/' . $fileName);
            $em->flush();

            return $this->json([
                'message' => 'Photo de profil mise à jour !',
                'photoUrl' => $user->getPhoto()
            ]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}