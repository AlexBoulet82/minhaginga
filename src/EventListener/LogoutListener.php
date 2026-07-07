<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class LogoutListener
{
    #[AsEventListener(event: LogoutEvent::class)]
    public function onLogout(LogoutEvent $event): void
    {
        // On crée notre réponse JSON personnalisée
        $response = new JsonResponse([
            'message' => 'Déconnexion réussie ! Sem preocupação !'
        ], JsonResponse::HTTP_OK);

        // On l'injecte dans l'événement : Symfony va l'envoyer directement 
        // au client et stopper le comportement de redirection par défaut !
        $event->setResponse($response);
    }
}