<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Doctrine\Persistence\ObjectManager;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Neucore\Application;
use Neucore\Entity\Alliance;
use Neucore\Entity\App;
use Neucore\Entity\Character;
use Neucore\Entity\Corporation;
use Neucore\Entity\CorporationMember;
use Neucore\Entity\EsiLocation;
use Neucore\Entity\EsiType;
use Neucore\Entity\Group;
use Neucore\Entity\GroupApplication;
use Neucore\Entity\Player;
use Neucore\Entity\RemovedCharacter;
use Neucore\Entity\Role;
use Neucore\Entity\SystemVariable;
use Neucore\Entity\Watchlist;
use Neucore\Factory\RepositoryFactory;
use Neucore\Service\SessionData;

class Helper
{
    /**
     * @var EntityManagerInterface
     */
    private static $em;

    /**
     * @var int
     */
    private static $roleSequence = 0;

    private $entities = [
        Watchlist::class,
        GroupApplication::class,
        App::class,
        CorporationMember::class,
        Character::class,
        RemovedCharacter::class,
        Player::class,
        Group::class,
        Role::class,
        Corporation::class,
        Alliance::class,
        SystemVariable::class,
        EsiType::class,
        EsiLocation::class,
    ];

    /**
     * @throws \Exception
     */
    public static function generateToken(
        array $scopes = ['scope1', 'scope2'],
        string $charName = 'Name',
        string $ownerHash = 'hash',
        string $ownerHashKey = 'owner'
    ): array {
        // create key
        $jwk = JWKFactory::createRSAKey(2048, ['alg' => 'RS256', 'use' => 'sig']);

        // create token
        $algorithmManager = new AlgorithmManager([new RS256()]);
        $jwsBuilder = new JWSBuilder($algorithmManager);
        $payload = (string)json_encode([
            'scp' => count($scopes) > 1 ? $scopes : ($scopes[0] ?? null),
            'sub' => 'CHARACTER:EVE:123',
            'name' => $charName,
            $ownerHashKey => $ownerHash,
            'exp' => time() + 3600,
            'iss' => 'login.eveonline.com',
        ]);
        $jws = $jwsBuilder
            ->create()
            ->withPayload($payload)
            ->addSignature($jwk, ['alg' => $jwk->get('alg')])
            ->build();
        $token = (new CompactSerializer())->serialize($jws);

        // create key set
        $keySet = [$jwk->toPublic()->jsonSerialize()];

        return [$token, $keySet];
    }

    public function resetSessionData(): void
    {
        unset($_SESSION);
        SessionData::setReadOnly(true);
    }

    public function getObjectManager(): ObjectManager
    {
        return $this->getEm();
    }

    public function getEm(): EntityManagerInterface
    {
        if (self::$em === null) {
            $conf = (new Application())->loadSettings(true)['doctrine'];

            $config = Setup::createAnnotationMetadataConfiguration(
                $conf['meta']['entity_paths'],
                $conf['meta']['dev_mode'],
                $conf['meta']['proxy_dir'],
                null,
                false
            );
            /** @noinspection PhpDeprecationInspection */
            /* @phan-suppress-next-line PhanDeprecatedFunction */
            AnnotationRegistry::registerLoader('class_exists');

            /** @noinspection PhpUnhandledExceptionInspection */
            $em = EntityManager::create($conf['connection'], $config);

            self::$em = $em;
        }

        return self::$em;
    }

    public function getDbName(): string
    {
        try {
            return $this->getEm()->getConnection()->getDatabasePlatform()->getName();
        } catch (DBALException $e) {
            return 'error';
        }
    }

    public function addEm(array $mocks): array
    {
        if (! array_key_exists(ObjectManager::class, $mocks)) {
            $mocks[ObjectManager::class] = (new self())->getEm();
        }
        if (! array_key_exists(EntityManagerInterface::class, $mocks)) {
            $mocks[EntityManagerInterface::class] = (new self())->getEm();
        }

        return $mocks;
    }

    /**
     * @throws DBALException
     */
    public function updateDbSchema(): void
    {
        $em = $this->getEm();

        $classes = [];
        foreach ($this->entities as $entity) {
            $classes[] = $em->getClassMetadata($entity);
        }

        $tool = new SchemaTool($em);
        if ($this->getDbName() === 'sqlite') {
            $tool->updateSchema($classes);
        } else {
            $em->getConnection()->exec('SET FOREIGN_KEY_CHECKS = 0;');
            $tool->updateSchema($classes);
            $em->getConnection()->exec('SET FOREIGN_KEY_CHECKS = 1;');
        }
    }

    /**
     * @noinspection SqlResolve
     */
    public function emptyDb(): void
    {
        $em = $this->getEm();
        $qb = $em->createQueryBuilder();

        foreach ($this->entities as $entity) {
            $qb->delete($entity)->getQuery()->execute();
        }

        if ($this->getDbName() === 'sqlite') {
            // for some reason these relation tables are not empties with SQLite in-memory db
            try {
                $em->getConnection()->exec('DELETE FROM watchlist_corporation WHERE 1');
                $em->getConnection()->exec('DELETE FROM watchlist_alliance WHERE 1');
                $em->getConnection()->exec('DELETE FROM watchlist_blacklist_corporation WHERE 1');
                $em->getConnection()->exec('DELETE FROM watchlist_blacklist_alliance WHERE 1');
                $em->getConnection()->exec('DELETE FROM watchlist_whitelist_corporation WHERE 1');
                $em->getConnection()->exec('DELETE FROM watchlist_whitelist_alliance WHERE 1');
            } catch (DBALException $e) {
                echo $e->getMessage();
            }
        }

        $em->clear();
    }

    /**
     * @param array $roles
     * @return Role[]
     */
    public function addRoles(array $roles): array
    {
        $om = $this->getObjectManager();
        $rr = (new RepositoryFactory($om))->getRoleRepository();

        $roleEntities = [];
        foreach ($roles as $roleName) {
            $role = $rr->findOneBy(['name' => $roleName]);
            if ($role === null) {
                self::$roleSequence ++;
                $role = new Role(self::$roleSequence);
                $role->setName($roleName);
                $om->persist($role);
            }
            $roleEntities[] = $role;
        }
        $om->flush();

        return $roleEntities;
    }

    /**
     * @param array $groups
     * @return Group[]
     */
    public function addGroups(array $groups): array
    {
        $om = $this->getObjectManager();
        $gr = (new RepositoryFactory($om))->getGroupRepository();

        $groupEntities = [];
        foreach ($groups as $groupName) {
            $group = $gr->findOneBy(['name' => $groupName]);
            if ($group === null) {
                $group = new Group();
                $group->setName($groupName);
                $om->persist($group);
            }
            $groupEntities[] = $group;
        }
        $om->flush();

        return $groupEntities;
    }

    public function addCharacterMain(string $name, int $charId, array $roles = [], array $groups = []): Character
    {
        $om = $this->getObjectManager();

        $player = new Player();
        $player->setName($name);

        $char = new Character();
        $char->setId($charId);
        $char->setName($name);
        $char->setMain(true);
        $char->setCharacterOwnerHash('123');
        $char->setAccessToken('abc');
        $char->setExpires(123456);
        $char->setRefreshToken('def');

        $char->setPlayer($player);
        $player->addCharacter($char);

        foreach ($this->addRoles($roles) as $role) {
            $player->addRole($role);
        }

        foreach ($this->addGroups($groups) as $group) {
            $player->addGroup($group);
        }

        $om->persist($player);
        $om->persist($char);
        $om->flush();

        return $char;
    }

    public function addCharacterToPlayer(string $name, int $charId, Player $player): Character
    {
        $alt = new Character();
        $alt->setId($charId);
        $alt->setName($name);
        $alt->setMain(false);
        $alt->setCharacterOwnerHash('456');
        $alt->setAccessToken('def');
        $alt->setPlayer($player);
        $player->addCharacter($alt);

        $this->getObjectManager()->persist($alt);
        $this->getObjectManager()->flush();

        return $alt;
    }

    public function addNewPlayerToCharacterAndFlush(Character $character)
    {
        $player = (new Player())->setName('Player');
        $character->setPlayer($player);
        $this->getObjectManager()->persist($player);
        $this->getObjectManager()->persist($character);
        $this->getObjectManager()->flush();
    }

    public function addApp(string $name, string $secret, array $roles, $hashAlgorithm = PASSWORD_BCRYPT): App
    {
        $hash = $hashAlgorithm === 'md5' ? crypt($secret, '$1$12345678$') : password_hash($secret, $hashAlgorithm);

        $app = new App();
        $app->setName($name);
        $app->setSecret((string) $hash);
        $this->getObjectManager()->persist($app);

        foreach ($this->addRoles($roles) as $role) {
            $app->addRole($role);
        }

        $this->getObjectManager()->flush();

        return $app;
    }
}
