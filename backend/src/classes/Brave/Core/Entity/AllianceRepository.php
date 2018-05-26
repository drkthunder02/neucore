<?php declare(strict_types=1);

namespace Brave\Core\Entity;

/**
 * AllianceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AllianceRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Constructor that makes this class autowireable.
     */
    public function __construct(\Doctrine\ORM\EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getMetadataFactory()->getMetadataFor(Alliance::class));
    }

    /**
     *
     * {@inheritDoc}
     * @see \Doctrine\ORM\EntityRepository::find()
     * @return \Brave\Core\Entity\Alliance|null
     */
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return parent::find($id, $lockMode, $lockVersion);
    }

    /**
     *
     * @return \Brave\Core\Entity\Alliance[]
     */
    public function getAllWithGroups()
    {
        return $this->createQueryBuilder('a')
        ->join('a.groups', 'g')
        ->andWhere('g.id IS NOT NULL')
        ->orderBy('a.name')
        ->getQuery()
        ->getResult();
    }
}
