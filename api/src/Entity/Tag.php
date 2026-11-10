<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Slug;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_TAG_SLUG', fields: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(extraProperties: ['standard_put' => false], security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['tag:read']],
    denormalizationContext: ['groups' => ['tag:write']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'slug' => 'partial',
    'entityTypes' => 'exact',
    'entityTypes.slug' => 'exact',
])]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    #[Groups(['tag:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['tag:read', 'tag:write'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Slug(fields: ['name'], updatable: false)]
    #[ApiProperty(writable: false)]
    #[Groups(['tag:read'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, EntityType>
     */
    #[ORM\ManyToMany(targetEntity: EntityType::class, inversedBy: 'tags')]
    #[ORM\JoinTable(name: 'tags_entity_types')]
    #[Assert\Count(min: 1)]
    #[Groups(['tag:read', 'tag:write'])]
    private Collection $entityTypes;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['tag:read', 'tag:write'])]
    private ?string $category = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['tag:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->entityTypes = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if (null === $this->slug && $this->name !== null) {
            $slugger = new AsciiSlugger();
            $this->slug = $slugger->slug($this->name)->lower()->toString();
        }
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

        if ($this->slug === null) {
            $this->generateSlug();
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        if ($this->slug !== null) {
            return $this;
        }

        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, EntityType>
     */
    public function getEntityTypes(): Collection
    {
        return $this->entityTypes;
    }

    public function setEntityTypes(iterable $entityTypes): self
    {
        if ($entityTypes instanceof Collection) {
            $this->entityTypes = $entityTypes;
        } else {
            $this->entityTypes = new ArrayCollection(is_array($entityTypes) ? $entityTypes : iterator_to_array($entityTypes));
        }

        return $this;
    }

    public function addEntityType(EntityType $entityType): self
    {
        if (!$this->entityTypes->contains($entityType)) {
            $this->entityTypes->add($entityType);
        }

        return $this;
    }

    public function removeEntityType(EntityType $entityType): self
    {
        $this->entityTypes->removeElement($entityType);

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;

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
}
