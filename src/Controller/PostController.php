<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/posts', name: 'api_posts_')]
class PostController extends AbstractController
{
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser(); 

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non authentifié.'], 401);
        }

        // Attention : la clé attendue dans le FormData est 'media'
        $file = $request->files->get('media');
        $caption = $request->request->get('caption');

        if (!$file) {
            return $this->json(['error' => 'Aucun fichier vidéo ou image transmis.'], 400);
        }

        // Détection du type de média (image ou vidéo)
        $mimeType = $file->getMimeType() ?? '';
        $mediaType = str_contains($mimeType, 'video') ? 'video' : 'image';

        // Génération d'un nom unique
        $fileName = uniqid() . '.' . ($file->guessExtension() ?? 'bin');

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/posts';

// Crée le dossier s'il n'existe pas encore
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Déplace le fichier
$file->move($uploadDir, $fileName);

        // Création et sauvegarde du Post
        $post = new Post();
        $post->setUser($user);
        $post->setCaption($caption);
        $post->setMediaUrl('/uploads/posts/' . $fileName);
        $post->setMediaType($mediaType);
        $post->setCreatedAt(new \DateTimeImmutable());

        $em->persist($post);
        $em->flush();

        return $this->json([
            'id' => $post->getId(),
            'mediaUrl' => 'http://localhost:8000' . $post->getMediaUrl(),
            'mediaType' => $post->getMediaType(),
            'caption' => $post->getCaption(),
            'createdAt' => $post->getCreatedAt()->format(\DateTime::ATOM),
            'user' => [
                'email' => $user->getEmail(),
                'avatar' => $user->getAvatar() ? 'http://localhost:8000' . $user->getAvatar() : null,
            ]
        ], 201);
    }
#[Route('', name: 'list', methods: ['GET'])]
public function list(PostRepository $postRepository): JsonResponse
{
    $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

    $data = array_map(function (Post $post) {
        // On récupère l'auteur spécifique du post
        $author = $post->getUser();

        return [
            'id' => $post->getId(),
            'mediaUrl' => $post->getMediaUrl() ? 'http://localhost:8000' . $post->getMediaUrl() : null,
            'mediaType' => $post->getMediaType(),
            'caption' => $post->getCaption(),
            'createdAt' => $post->getCreatedAt()->format(\DateTime::ATOM),
            // Les informations de l'auteur du post :
            'user' => [
                'id' => $author ? $author->getId() : null,
                'email' => $author ? $author->getEmail() : 'Anonyme',
                'apelido' => $author ? $author->getApelido() : null,
                'avatar' => ($author && $author->getAvatar()) ? 'http://localhost:8000' . $author->getAvatar() : null,
            ]
        ];
    }, $posts);

    return $this->json($data, 200);
}
}