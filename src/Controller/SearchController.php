<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/api/public/search/capoeiristes', name: 'app_api_search_capoeiristes', methods: ['GET'])]
    public function searchCapoeiristes(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        // On cherche uniquement les comptes de type 'eleve' qui ont choisi d'être publics
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.accountType = :type')
            ->andWhere('u.isPublic = :isPublic')
            ->setParameter('type', 'eleve')
            ->setParameter('isPublic', true);

        // On filtre en SQL uniquement sur les champs sans caractères spéciaux
        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.Apelido) LIKE LOWER(:query)',
                    'LOWER(u.firstname) LIKE LOWER(:query)',
                    'LOWER(u.lastname) LIKE LOWER(:query)'
                )
            )->setParameter('query', '%' . $query . '%');
        }

        $users = $qb->getQuery()->getResult();
        $results = [];

        foreach ($users as $user) {
            // Optionnel : Si l'utilisateur recherche un grade (ex: "Mestre"), on l'inclut aussi grâce à PHP
            // pour éviter que le "ç" ne fasse planter la base de données !
            $matchGrade = false;
            if (!empty($query) && $user->getGraduaçao()) {
                $matchGrade = (stripos($user->getGraduaçao(), $query) !== false);
            }

            // Si la requête correspond au nom/prénom (déjà filtré par SQL) OU au grade (filtré par PHP)
            if (empty($query) || $matchGrade || stripos($user->getApelido() ?? '', $query) !== false || stripos($user->getFirstname() ?? '', $query) !== false) {
                $results[] = [
                    'id' => $user->getId(),
                    'apelido' => $user->getApelido(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'graduacao' => $user->getGraduaçao(),
                    'photo' => $user->getPhoto(),
                ];
            }
        }

        return $this->json($results);
    }

    #[Route('/api/public/search/academies', name: 'app_api_search_academies', methods: ['GET'])]
    public function searchAcademies(Request $request, UserRepository $userRepository): JsonResponse
    {
        $query = $request->query->get('q', '');

        // On cherche uniquement les comptes de type 'academie'
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.accountType = :type')
            ->setParameter('type', 'academie');

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(u.firstname) LIKE LOWER(:query)', // Nom de l'école
                    'LOWER(u.description) LIKE LOWER(:query)' // Description ou Ville
                )
            )->setParameter('query', '%' . $query . '%');
        }

        $academies = $qb->getQuery()->getResult();
        $results = [];

        foreach ($academies as $academy) {
            $results[] = [
                'id' => $academy->getId(),
                'nom_ecole' => $academy->getFirstname(),
                'description' => $academy->getDescription(),
                'photo' => $academy->getPhoto(),
            ];
        }

        return $this->json($results);
    }
}