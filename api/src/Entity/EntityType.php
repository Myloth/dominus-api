<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\EntityTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EntityTypeRepository::class)]
#[ORM\Table(name: 'entity_type')]
#[ORM\UniqueConstraint(name: 'UNIQ_ENTITY_TYPE_SLUG', fields: ['slug'])]
#[UniqueEntity(fields: ['slug'], message: 'This slug is already in use.')]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
    ],
    normalizationContext: ['groups' => ['entity_type:read']],
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'slug' => 'exact',
])]
class EntityType
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column]
    #[Groups(['entity_type:read', 'tag:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['entity_type:read', 'tag:read'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Groups(['entity_type:read', 'tag:read'])]
    private ?string $slug = null;

    /**
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, mappedBy: 'entityTypes')]
    private Collection $tags;

    public function __construct()
    {
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addEntityType($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeEntityType($this);
        }

        return $this;
    }
}
