<?php

namespace App\Entity;

use App\Base\Entity\AbstractEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * Battle
 *
 * @ORM\Table(name="battle", indexes={@ORM\Index(name="battle_battle_status_id", columns={"battle_status_id"}), @ORM\Index(name="battle_battle_type_id", columns={"battle_type_id"})})
 * @ORM\Entity
 */
class Battle
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="data", type="text", length=0, nullable=false)
     */
    private $data;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    private $createdAt = 'current_timestamp()';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    private $updatedAt = 'current_timestamp()';

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true, options={"default"="NULL"})
     */
    private $deletedAt = 'NULL';

    /**
     * @var \BattleStatus
     *
     * @ORM\ManyToOne(targetEntity="BattleStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="battle_status_id", referencedColumnName="id")
     * })
     */
    private $battleStatus;

    /**
     * @var \BattleType
     *
     * @ORM\ManyToOne(targetEntity="BattleType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="battle_type_id", referencedColumnName="id")
     * })
     */
    private $battleType;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeInterface $deletedAt): self
    {
        $this->deletedAt = $deletedAt;

        return $this;
    }

    public function getBattleStatus(): ?BattleStatus
    {
        return $this->battleStatus;
    }

    public function setBattleStatus(?BattleStatus $battleStatus): self
    {
        $this->battleStatus = $battleStatus;

        return $this;
    }

    public function getBattleType(): ?BattleType
    {
        return $this->battleType;
    }

    public function setBattleType(?BattleType $battleType): self
    {
        $this->battleType = $battleType;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'battleStatus' => $this->getBattleStatus()->toArray(),
            'battleType' => $this->getBattleType()->getId(),
            'data' => \json_decode($this->getData(), true)
        ];
    }
}
