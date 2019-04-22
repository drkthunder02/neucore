<?php declare(strict_types=1);

namespace Brave\Core\Entity;

use Brave\Core\Api;
use Swagger\Annotations as SWG;
use Doctrine\ORM\Mapping as ORM;

/**
 * @SWG\Definition(
 *     definition="CorporationMember",
 *     required={"id", "name"},
 *     description="The player property contains only id and name, character does not contain corporation.",
 *     @SWG\Property(
 *         property="player",
 *         ref="#/definitions/Player"
 *     )
 * )
 * @ORM\Entity
 * @ORM\Table(name="corporation_members")
 */
class CorporationMember implements \JsonSerializable
{
    /**
     * EVE Character ID.
     *
     * @SWG\Property(format="int64")
     * @ORM\Id
     * @ORM\Column(type="bigint")
     * @ORM\GeneratedValue(strategy="NONE")
     * @var integer
     */
    private $id;

    /**
     * EVE Character name.
     *
     * @SWG\Property()
     * @ORM\Column(type="string", length=255, nullable=true)
     * @var string
     */
    private $name;

    /**
     * Corporation ticker.
     *
     * @SWG\Property()
     * @ORM\Column(type="bigint", name="location_id", nullable=true)
     * @var integer
     */
    private $locationId;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="logoff_date", nullable=true)
     * @var \DateTime
     */
    private $logoffDate;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="logon_date", nullable=true)
     * @var \DateTime
     */
    private $logonDate;

    /**
     * @SWG\Property()
     * @ORM\Column(type="bigint", name="ship_type_id", nullable=true)
     * @var integer
     */
    private $shipTypeId;

    /**
     * @SWG\Property()
     * @ORM\Column(type="datetime", name="start_date", nullable=true)
     * @var \DateTime
     */
    private $startDate;

    /**
     * @ORM\ManyToOne(targetEntity="Corporation", inversedBy="members")
     * @var Corporation
     */
    private $corporation;

    /**
     * @SWG\Property()
     * @ORM\OneToOne(targetEntity="Character", inversedBy="corporationMember")
     * @var Character
     */
    private $character;

    /**
     * {@inheritDoc}
     * @see \JsonSerializable::jsonSerialize()
     */
    public function jsonSerialize($forUser = true)
    {
        $result = [
            'id' => $this->getId(),
            'name' => $this->name,
            'locationId' => $this->locationId,
            'logoffDate' => $this->logoffDate ? $this->logoffDate->format(Api::DATE_FORMAT) : null,
            'logonDate' => $this->logonDate ? $this->logonDate->format(Api::DATE_FORMAT) : null,
            'shipTypeId' => $this->shipTypeId,
            'startDate' => $this->startDate ? $this->startDate->format(Api::DATE_FORMAT) : null,
        ];

        if ($forUser) {
            $result = array_merge($result, [
                'character' => $this->character ? $this->character->jsonSerialize(false) : null,
                'player' => $this->character && $this->character->getPlayer() ?
                    $this->character->getPlayer()->jsonSerialize(true) : null,
            ]);
        }

        return $result;
    }

    /**
     * Set id.
     *
     * @param int $id
     *
     * @return CorporationMember
     */
    public function setId($id)
    {
        $this->id = (int) $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // cast to int because Doctrine creates string for type bigint
        return $this->id !== null ? (int) $this->id : null;
    }

    /**
     * Set name.
     *
     * @param string|null $name
     *
     * @return CorporationMember
     */
    public function setName($name = null)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set locationId.
     *
     * @param int|null $locationId
     *
     * @return CorporationMember
     */
    public function setLocationId($locationId = null)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Get locationId.
     *
     * @return int|null
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set logoffDate.
     *
     * @param \DateTime|null $logoffDate
     *
     * @return CorporationMember
     */
    public function setLogoffDate($logoffDate = null)
    {
        $this->logoffDate = $logoffDate ? clone $logoffDate : null;

        return $this;
    }

    /**
     * Get logoffDate.
     *
     * @return \DateTime|null
     */
    public function getLogoffDate()
    {
        return $this->logoffDate;
    }

    /**
     * Set logonDate.
     *
     * @param \DateTime|null $logonDate
     *
     * @return CorporationMember
     */
    public function setLogonDate($logonDate = null)
    {
        $this->logonDate = $logonDate ? clone $logonDate : null;

        return $this;
    }

    /**
     * Get logonDate.
     *
     * @return \DateTime|null
     */
    public function getLogonDate()
    {
        return $this->logonDate;
    }

    /**
     * Set shipTypeId.
     *
     * @param int|null $shipTypeId
     *
     * @return CorporationMember
     */
    public function setShipTypeId($shipTypeId = null)
    {
        $this->shipTypeId = $shipTypeId;

        return $this;
    }

    /**
     * Get shipTypeId.
     *
     * @return int|null
     */
    public function getShipTypeId()
    {
        return $this->shipTypeId;
    }

    /**
     * Set startDate.
     *
     * @param \DateTime|null $startDate
     *
     * @return CorporationMember
     */
    public function setStartDate($startDate = null)
    {
        $this->startDate = $startDate ? clone $startDate : null;

        return $this;
    }

    /**
     * Get startDate.
     *
     * @return \DateTime|null
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set corporation.
     *
     * @param Corporation|null $corporation
     *
     * @return CorporationMember
     */
    public function setCorporation(Corporation $corporation = null)
    {
        $this->corporation = $corporation;

        return $this;
    }

    /**
     * Get corporation.
     *
     * @return Corporation|null
     */
    public function getCorporation()
    {
        return $this->corporation;
    }

    /**
     * Set character.
     *
     * @param Character|null $character
     *
     * @return CorporationMember
     */
    public function setCharacter(Character $character = null)
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Get character.
     *
     * @return Character|null
     */
    public function getCharacter()
    {
        return $this->character;
    }
}
