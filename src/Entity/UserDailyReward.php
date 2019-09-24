<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserDailyReward
 *
 * @ORM\Table(name="user_daily_reward", indexes={@ORM\Index(name="user_daily_reward_daily_reward_type_id", columns={"daily_reward_type_id"}), @ORM\Index(name="user_daily_reward_user_id", columns={"user_id"})})
 * @ORM\Entity
 */
class UserDailyReward
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
     * @ORM\Column(name="claimed", type="datetime", nullable=false, options={"default"="current_timestamp()"})
     */
    private $claimed = 'current_timestamp()';

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
     * @var \DailyRewardType
     *
     * @ORM\ManyToOne(targetEntity="DailyRewardType")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="daily_reward_type_id", referencedColumnName="id")
     * })
     */
    private $dailyRewardType;

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

    public function getClaimed(): ?\DateTimeInterface
    {
        return $this->claimed;
    }

    public function setClaimed(\DateTimeInterface $claimed): self
    {
        $this->claimed = $claimed;

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

    public function getDailyRewardType(): ?DailyRewardType
    {
        return $this->dailyRewardType;
    }

    public function setDailyRewardType(?DailyRewardType $dailyRewardType): self
    {
        $this->dailyRewardType = $dailyRewardType;

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


}
