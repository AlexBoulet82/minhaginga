<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UserController extends AbstractController
{
    /**
     * Récupération des données du profil connecté
     */
    #[Route('/api/profile', name: 'app_api_profile', methods: ['GET'])]
    public function profile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'accountType' => $user->getAccountType(),
            'avatar' => $user->getAvatar() ? 'http://localhost:8000' . $user->getAvatar() : null,
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

    /**
     * Upload de la photo de profil (avatar)
     */
  #[Route('/api/user/avatar', name: 'app_api_user_avatar', methods: ['POST'])]
    public function uploadAvatar(
        Request $request,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('avatar');

        // ⚠️ Vérification : le fichier existe ET a bien été uploadé sans erreur
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'Aucun fichier valide n\'a été transmis.'], 400);
        }

        // Validation du MIME type (effectuée uniquement si le fichier est valide)
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return $this->json(['error' => 'Le fichier doit être une image (JPG, PNG, WEBP).'], 400);
        }

        // Dossier de destination
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Supprimer l'ancien avatar s'il existe
        if ($user->getAvatar()) {
            $oldAvatarPath = $this->getParameter('kernel.project_dir') . '/public' . $user->getAvatar();
            if (file_exists($oldAvatarPath) && is_file($oldAvatarPath)) {
                @unlink($oldAvatarPath);
            }
        }

        // Nom unique de fichier
        $fileName = uniqid('avatar_') . '.' . ($file->guessExtension() ?? 'png');

        try {
            $file->move($uploadDir, $fileName);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Erreur lors de la sauvegarde de l\'image.'], 500);
        }

        // Mise à jour de l'entité User
        $relativePath = '/uploads/avatars/' . $fileName;
        $user->setAvatar($relativePath);

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Avatar mis à jour avec succès.',
            'avatarUrl' => 'http://localhost:8000' . $relativePath,
        ], 200);
    }}