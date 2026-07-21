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

        // Enregistrement physique du fichier dans public/uploads/posts/
        $file->move($this->getParameter('kernel.project_dir') . '/public/uploads/posts', $fileName);

        // Création et sauvegarde du Post
        $post = new Post();
        $post->setUser($user);
        $post->setCaption($caption);
        $post->setMediaUrl('/uploads/posts/' . $fileName);
        $post->setMediaType($mediaType);
        $post->setCreatedAt(new \DateTimeImmutable());

        $em->persist($post);
        $em->flush();

        return $this->json($post, 201, [], ['groups' => 'post:read']);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(PostRepository $postRepository): JsonResponse
    {
        $posts = $postRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->json($posts, 200, [], ['groups' => 'post:read']);
    }
}