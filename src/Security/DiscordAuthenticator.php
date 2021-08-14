<?php

namespace App\Security;

use App\Security\DiscordUserProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DiscordAuthenticator extends AbstractGuardAuthenticator
{
    private CsrfTokenManagerInterface $csrfTokenManager;

    private DiscordUserProvider $discordUserProvider;

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        DiscordUserProvider $discordUserProvider,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->discordUserProvider = $discordUserProvider;
        $this->urlGenerator = $urlGenerator;
    }
    
    public function supports(Request $request): bool
    {
        return $request->query->has('discord-oauth-provider');
    }

    public function getCredentials(Request $request): array
    {
        $state = $request->query->get('state');

        if (null === $state || !$this->csrfTokenManager->isTokenValid(new CsrfToken('oauth-discord-SF', $state))) {
            throw new AccessDeniedException('Wrong token');
        }

        return [
            'code' => $request->query->get('code')
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            return null;
        }

        return $this->discordUserProvider->loadUserFromDiscordOAuth($credentials['code']);
    }

    public function checkCredentials($credentials, UserInterface $user): bool
    {
        return true;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'message' => 'Authentification refusée'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('user.profil'));
    }

    public function start(Request $request, AuthenticationException $authException = null): RedirectResponse
    {
        return new RedirectResponse($this->urlGenerator->generate('index'));
        /*return new JsonResponse([
            'message' => 'Authentification requise'
        ], Response::HTTP_UNAUTHORIZED);*/
    }

    public function supportsRememberMe(): bool
    {
        return false;
    }
}
