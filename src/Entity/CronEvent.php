<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * CronEvent
 *
 * @ORM\Table(name="cron_event", indexes={@ORM\Index(name="cront_event_type_id", columns={"cron_event_type_id"})})
 * @ORM\Entity
 */
class CronEvent
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
     * @ORM\Column(name="expired_date_time", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    private $expiredDateTime = 'current_timestamp()';

    /**
     * @var string
     *
     * @ORM\Column(name="key_id", type="string", length=32, nullable=false)
     */
    private $keyId;

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
     * @var \CronEventType
     *
     * @ORM\ManyToOne(targetEntity="CronEventType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="cron_event_type_id", referencedColumnName="id")
     * })
     */
    private $cronEventType;

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

    public function getExpiredDateTime(): ?\DateTimeInterface
    {
        return $this->expiredDateTime;
    }

    public function setExpiredDateTime(\DateTimeInterface $expiredDateTime): self
    {
        $this->expiredDateTime = $expiredDateTime;

        return $this;
    }

    public function getKeyId(): ?string
    {
        return $this->keyId;
    }

    public function setKeyId(string $keyId): self
    {
        $this->keyId = $keyId;

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

    public function getCronEventType(): ?CronEventType
    {
        return $this->cronEventType;
    }

    public function setCronEventType(?CronEventType $cronEventType): self
    {
        $this->cronEventType = $cronEventType;

        return $this;
    }


}
