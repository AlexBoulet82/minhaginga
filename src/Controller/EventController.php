<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class EventController extends AbstractController
{
    #[Route('/api/events', name: 'app_api_events_create', methods: ['POST'])]
    public function create(
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour créer un événement.'], 401);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title']) || empty($data['startAt']) || empty($data['location'])) {
            return $this->json(['error' => 'Les champs titre, date de début et lieu sont obligatoires.'], 400);
        }

        $event = new Event();
        $event->setTitle($data['title']);
        $event->setDescription($data['description'] ?? null);
        $event->setLocation($data['location']);
        $event->setEventType($data['eventType'] ?? 'Roda');
        
        try {
            $event->setStartAt(new \DateTimeImmutable($data['startAt']));
        } catch (\Exception $e) {
            return $this->json(['error' => 'Format de date invalide (attendu: YYYY-MM-DD HH:MM:SS).'], 400);
        }

        $event->setOrganizer($user);

        $em->persist($event);
        $em->flush();

        return $this->json([
            'message' => 'Événement créé avec succès !',
            'event' => [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'location' => $event->getLocation(),
                'startAt' => $event->getStartAt()->format('Y-m-d H:i:s'),
            ]
        ], JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/events', name: 'app_api_events_list', methods: ['GET'])]
    public function list(EventRepository $eventRepository): JsonResponse
    {
        $eventsFromDb = $eventRepository->findBy([], ['startAt' => 'ASC']);
        $events = [];

        foreach ($eventsFromDb as $event) {
            $organizer = $event->getOrganizer();
            
            // On extrait la liste des participants sous forme de tableau simplifié
            $participants = [];
            foreach ($event->getParticipants() as $participant) {
                $participants[] = [
                    'id' => $participant->getId(),
                    'apelido' => $participant->getApelido() ?? $participant->getFirstname()
                ];
            }

            $events[] = [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                'description' => $event->getDescription(),
                'location' => $event->getLocation(),
                'eventType' => $event->getEventType(),
                'startAt' => $event->getStartAt()->format('Y-m-d H:i:s'),
                'organizer' => [
                    'id' => $organizer->getId(),
                    'name' => $organizer->getAccountType() === 'academie' ? $organizer->getFirstname() : $organizer->getApelido()
                ],
                'participants' => $participants,
                'participantsCount' => count($participants)
            ];
        }

        return $this->json($events);
    }

    #[Route('/api/events/{id}', name: 'app_api_events_update', methods: ['PUT'])]
    public function update(
        Event $event,
        #[CurrentUser] ?User $user,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        // Sécurité : Seul l'organisateur peut modifier son événement
        if (!$user || $event->getOrganizer() !== $user) {
            return $this->json(['error' => 'Action non autorisée. Vous devez être l\'organisateur de cet événement.'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $event->setTitle($data['title']);
        if (isset($data['description'])) $event->setDescription($data['description']);
        if (isset($data['location'])) $event->setLocation($data['location']);
        if (isset($data['eventType'])) $event->setEventType($data['eventType']);
        
        if (isset($data['startAt'])) {
            try {
                $event->setStartAt(new \DateTimeImmutable($data['startAt']));
            } catch (\Exception $e) {
                return $this->json(['error' => 'Format de date invalide.'], 400);
            }
        }

        $em->flush();

        return $this->json(['message' => 'Événement mis à jour avec succès !']);
    }

    #[Route('/api/events/{id}', name: 'app_api_events_delete', methods: ['DELETE'])]
    public function delete(
        Event $event,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        // Sécurité : Seul l'organisateur peut supprimer son événement
        if (!$user || $event->getOrganizer() !== $user) {
            return $this->json(['error' => 'Action non autorisée.'], 403);
        }

        $em->remove($event);
        $em->flush();

        return $this->json(['message' => 'Événement supprimé avec succès.']);
    }

    #[Route('/api/events/{id}/participate', name: 'app_api_events_toggle_participate', methods: ['POST'])]
    public function toggleParticipation(
        Event $event,
        #[CurrentUser] ?User $user,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$user) {
            return $this->json(['error' => 'Vous devez être connecté pour participer.'], 401);
        }

        // Si l'utilisateur participe déjà, on le retire. Sinon, on l'ajoute (système de bouton "bascule")
        if ($event->getParticipants()->contains($user)) {
            $event->removeParticipant($user);
            $message = 'Vous ne participez plus à cet événement.';
            $participating = false;
        } else {
            $event->addParticipant($user);
            $message = 'Inscription enregistrée ! Vous participez à cet événement.';
            $participating = true;
        }

        $em->flush();

        return $this->json([
            'message' => $message,
            'participating' => $participating,
            'participantsCount' => count($event->getParticipants())
        ]);
    }
}