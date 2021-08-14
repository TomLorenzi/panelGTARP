<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LoginController extends AbstractController
{
    private const DISCORD_ENDPOINT = 'https://discord.com/api/oauth2/authorize';

    /**
     * @Route("/discord-login", name="login.discord", methods={"GET"})
     */
    public function login(
        CsrfTokenManagerInterface $csrfTokenManager,
        UrlGeneratorInterface $urlGenerator
    ): RedirectResponse {
        $redirectUrl = $urlGenerator->generate('user.profil', [
            'discord-oauth-provider' => true
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $queryParams = \http_build_query([
            'client_id' => $this->getParameter('app.discord_client_id'),
            'prompt' => 'consent',
            'redirect_uri' => $redirectUrl,
            'response_type' => 'code',
            'scope' => 'guilds identify email',
            'state' => $csrfTokenManager->getToken('oauth-discord-SF')->getValue()
        ]);

        return new RedirectResponse(self::DISCORD_ENDPOINT . "?$queryParams");
    }
}
