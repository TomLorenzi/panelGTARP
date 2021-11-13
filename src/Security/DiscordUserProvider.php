<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class DiscordUserProvider implements UserProviderInterface
{
    private const DISCORD_ACCESS_TOKEN_ENDPOINT = 'https://discord.com/api/oauth2/token';

    private const DISCORD_USER_DATA_ENDPOINT = 'https://discordapp.com/api/users/@me';

    //TODO: Move to .env
    private const SINCITY_DISCORD_ID = '715458706929614909';

    private HttpClientInterface $httpClient;

    private string $discordClientId;

    private string $discordClientSecret;

    private UrlGeneratorInterface $urlGenerator;

    private UserRepository $userRepository;

    public function __construct(
        HttpClientInterface $httpClient,
        string $discordClientId,
        string $discordClientSecret,
        UrlGeneratorInterface $urlGenerator,
        UserRepository $userRepository
    ) {
        $this->httpClient = $httpClient;
        $this->discordClientId = $discordClientId;
        $this->discordClientSecret = $discordClientSecret;
        $this->urlGenerator = $urlGenerator;
        $this->userRepository = $userRepository;
    }

    public function loadUserFromDiscordOAuth(string $code): User
    {
        $accessToken = $this->getAccessToken($code);

        $discordUserData = $this->getUserInformations($accessToken);

        [
            'email' => $email,
            'id' => $discordId,
            'username' => $discordUserName,
            'avatar' => $avatarId
        ] = $discordUserData;

        $user = $this->userRepository->getUserFromOAuth($discordId);

        if (null === $user) {
            $user = $this->userRepository->createUserFromOAuth($discordId, $discordUserName, $email, $avatarId);
        }

        return $user;
    }

    public function loadUserByUsername(string $discordId): User
    {
        $user = $this->userRepository->findOneByDiscordId($discordId);

        if (null === $user) {
            throw new UsernameNotFoundException('Utilisateur non existant');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceOf User || !$user->getDiscordId()) {
            throw new UnsupportedUserException();
        }

        $discordId = $user->getDiscordId();

        return $this->loadUserByUsername($discordId);
    }

    public function supportsClass(string $class): bool
    {
        return User::class == $class;
    }

    private function getAccessToken(string $code): string
    {
        $redirectUrl = $this->urlGenerator->generate('user.profil', [
            'discord-oauth-provider' => true
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'client_id' => $this->discordClientId,
                'client_secret' => $this->discordClientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUrl,
                'scope' => 'guilds identify email'
            ]
        ];

        $response = $this->httpClient->request('POST', self::DISCORD_ACCESS_TOKEN_ENDPOINT, $options);

        $data = $response->toArray();

        if (!$data['access_token']) {
            throw new ServiceUnavailableHttpException(null, 'Authentification via Discord échoué');
        }

        return $data['access_token'];
    }

    private function getUserInformations(string $accessToken): array
    {
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => "Bearer {$accessToken}"
            ]
        ];

        $response = $this->httpClient->request('GET', self::DISCORD_USER_DATA_ENDPOINT, $options);

        $listUserGuilds = $this->httpClient->request('GET', self::DISCORD_USER_DATA_ENDPOINT . '/guilds', $options)->toArray();

        $isInServerGuild = false;
        foreach ($listUserGuilds as $guild) {
            if ($guild['id'] === self::SINCITY_DISCORD_ID) {
                $isInServerGuild = true;
                break;
            }
        }

        if (!$isInServerGuild) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Vous n\'êtes pas sur le Discord de SinCity');
        }

        $data = $response->toArray();

        if (!$data['email'] || !$data['id'] || !$data['username']) {
            throw new ServiceUnavailableHttpException(null, 'Problème API Discord ou structure de la réponse modifié');
        } else if (!$data['verified']) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Votre compte Discord doit être vérifié');
        }

        return $data;
    }
}