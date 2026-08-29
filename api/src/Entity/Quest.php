<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\QuestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuestRepository::class)]
#[ORM\Table(name: 'quest')]
#[ORM\UniqueConstraint(name: 'UNIQ_QUEST_NAME', fields: ['name'])]
#[UniqueEntity(fields: ['name'], message: 'This quest name is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'tags.slug' => 'exact',
    'name' => 'partial',
    'event.id' => 'exact',
])]
class Quest
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Assert\PositiveOrZero]
    private ?int $nivGuildeReq = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'nextQuests')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Quest $preReqQuest = null;

    /**
     * @var Collection<int, Quest>
     */
    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'preReqQuest')]
    private Collection $nextQuests;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'quests')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Event $event = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $objectives = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $rewards = [];

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $unlockLore = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    #[ORM\JoinTable(name: 'quests_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->nextQuests = new ArrayCollection();
        $this->tags = new ArrayCollection();
    }

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

    public function getNivGuildeReq(): ?int
    {
        return $this->nivGuildeReq;
    }

    public function setNivGuildeReq(?int $nivGuildeReq): self
    {
        $this->nivGuildeReq = $nivGuildeReq;

        return $this;
    }

    public function getPreReqQuest(): ?self
    {
        return $this->preReqQuest;
    }

    public function setPreReqQuest(?self $preReqQuest): self
    {
        $this->preReqQuest = $preReqQuest;

        return $this;
    }

    /**
     * @return Collection<int, Quest>
     */
    public function getNextQuests(): Collection
    {
        return $this->nextQuests;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getObjectives(): array
    {
        return $this->objectives;
    }

    /**
     * @param array<string, mixed> $objectives
     */
    public function setObjectives(array $objectives): self
    {
        $this->objectives = $objectives;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRewards(): array
    {
        return $this->rewards;
    }

    /**
     * @param array<string, mixed> $rewards
     */
    public function setRewards(array $rewards): self
    {
        $this->rewards = $rewards;

        return $this;
    }

    public function getUnlockLore(): ?string
    {
        return $this->unlockLore;
    }

    public function setUnlockLore(?string $unlockLore): self
    {
        $this->unlockLore = $unlockLore;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(iterable $tags): self
    {
        if ($tags instanceof Collection) {
            $this->tags = $tags;
        } else {
            $this->tags = new ArrayCollection(is_array($tags) ? $tags : iterator_to_array($tags));
        }

        return $this;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        $this->tags->removeElement($tag);

        return $this;
    }
}
