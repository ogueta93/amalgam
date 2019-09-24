<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserBooster
 *
 * @ORM\Table(name="user_booster", indexes={@ORM\Index(name="user_booster_booster_type_id", columns={"booster_type_id"}), @ORM\Index(name="user_booster_user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class UserBooster
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
     * @var \DateTime|null
     *
     * @ORM\Column(name="opened", type="datetime", nullable=true, options={"default"="NULL"})
     */
    private $opened = 'NULL';

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
     * @var \BoosterType
     *
     * @ORM\ManyToOne(targetEntity="BoosterType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="booster_type_id", referencedColumnName="id")
     * })
     */
    private $boosterType;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOpened(): ?\DateTimeInterface
    {
        return $this->opened;
    }

    public function setOpened(?\DateTimeInterface $opened): self
    {
        $this->opened = $opened;

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

    public function getBoosterType(): ?BoosterType
    {
        return $this->boosterType;
    }

    public function setBoosterType(?BoosterType $boosterType): self
    {
        $this->boosterType = $boosterType;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'boosterType' => $this->getBoosterType()->toArray(),
            'opened' => $this->getOpened()
        ];
    }
}
