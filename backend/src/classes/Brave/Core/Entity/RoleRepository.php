<?php

namespace Brave\Core\Entity;

/**
 * RoleRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RoleRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Constructor that makes this class autowireable.
     */
    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getMetadataFactory()->getMetadataFor(Role::class));
    }
}