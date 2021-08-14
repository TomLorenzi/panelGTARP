<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    private const IMAGE_BASE_URL = 'https://cdn.discordapp.com/';
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function getUserFromOAuth(
        string $discordId
    ): ?User {
        return $this->findOneByDiscordId($discordId);   
    }

    public function createUserFromOAuth(
        string $discordId,
        string $discordUsername,
        string $email,
        string $avatarId
    ): ?User {
        $user = $this->findOneByDiscordId($discordId);

        if (null !== $user) {
            return null;
        }

        $user = new User();
        $user->setDiscordId($discordId);
        $user->setUsername($discordUsername);
        $user->setEmail($email);

        $extension = 'png';
        if ('a_' === substr($avatarId, 0, 2)) {
            $extension = 'gif';
        }

        $discordUrl = self::IMAGE_BASE_URL . "avatars/$discordId/$avatarId.$extension";
        $user->setAvatar($discordUrl);

        $this->_em->persist($user);
        $this->_em->flush();

        return $user;
    }

    public function refreshUser(
        string $discordId,
        string $discordUsername,
        string $email,
        string $avatarId
    ): ?User {
        $user = $this->findOneByDiscordId($discordId);

        if (null === $user) {
            return null;
        }

        $user->setUsername($discordUsername);
        $user->setEmail($email);

        $extension = 'png';
        if ('a_' === substr($avatarId, 0, 2)) {
            $extension = 'gif';
        }

        $discordUrl = self::IMAGE_BASE_URL . "avatars/$discordId/$avatarId.$extension";
        $this->em->setAvatar($discordUrl);

        $this->_em->persist($user);
        $this->_em->flush();

        return $user;
    }
}
