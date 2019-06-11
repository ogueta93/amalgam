<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Card
 *
 * @ORM\Table(name="card", uniqueConstraints={@ORM\UniqueConstraint(name="card_name_unique", columns={"name"})}, indexes={@ORM\Index(name="card_type_id", columns={"type_id"})})
 * @ORM\Entity
 */
class Card
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="ctop", type="string", length=3, nullable=false)
     */
    private $ctop;

    /**
     * @var string
     *
     * @ORM\Column(name="cright", type="string", length=3, nullable=false)
     */
    private $cright;

    /**
     * @var string
     *
     * @ORM\Column(name="cbottom", type="string", length=3, nullable=false)
     */
    private $cbottom;

    /**
     * @var string
     *
     * @ORM\Column(name="cleft", type="string", length=3, nullable=false)
     */
    private $cleft;

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
     * @var \CardType
     *
     * @ORM\ManyToOne(targetEntity="CardType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     */
    private $type;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCtop(): ?string
    {
        return $this->ctop;
    }

    public function setCtop(string $ctop): self
    {
        $this->ctop = $ctop;

        return $this;
    }

    public function getCright(): ?string
    {
        return $this->cright;
    }

    public function setCright(string $cright): self
    {
        $this->cright = $cright;

        return $this;
    }

    public function getCbottom(): ?string
    {
        return $this->cbottom;
    }

    public function setCbottom(string $cbottom): self
    {
        $this->cbottom = $cbottom;

        return $this;
    }

    public function getCleft(): ?string
    {
        return $this->cleft;
    }

    public function setCleft(string $cleft): self
    {
        $this->cleft = $cleft;

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

    public function getType(): ?CardType
    {
        return $this->type;
    }

    public function setType(?CardType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function toArray()
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => $this->getType()->toArray(),
            'top' => $this->getCtop(),
            'right' => $this->getCright(),
            'bottom' => $this->getCbottom(),
            'left' => $this->getCleft()
        ];
    }
}
