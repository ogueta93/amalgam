<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserCard
 *
 * @ORM\Table(name="user_card", indexes={@ORM\Index(name="user_card_battle_id", columns={"id_battle"}), @ORM\Index(name="user_card_card_id", columns={"id_card"}), @ORM\Index(name="user_card_user_id", columns={"id_user"})})
 * @ORM\Entity
 */
class UserCard
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
     * @var \Battle
     *
     * @ORM\ManyToOne(targetEntity="Battle")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_battle", referencedColumnName="id")
     * })
     */
    private $idBattle;

    /**
     * @var \Card
     *
     * @ORM\ManyToOne(targetEntity="Card")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_card", referencedColumnName="id")
     * })
     */
    private $idCard;

    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="id_user", referencedColumnName="id")
     * })
     */
    private $idUser;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getIdCard(): ?Card
    {
        return $this->idCard;
    }

    public function setIdCard(?Card $idCard): self
    {
        $this->idCard = $idCard;

        return $this;
    }

    public function getIdUser(): ?User
    {
        return $this->idUser;
    }

    public function setIdUser(?User $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getIdBattle(): ?Battle
    {
        return $this->idBattle;
    }

    public function setIdBattle(?Battle $idBattle): self
    {
        $this->idBattle = $idBattle;

        return $this;
    }
}
