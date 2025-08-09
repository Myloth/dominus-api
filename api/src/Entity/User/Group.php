<?php

namespace App\Entity\User;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiFilter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Slug;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotations as JMS;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

/**
 * Class Group
 */
#[ORM\Entity]
#[ORM\Table(name: 'user_group')]
#[ApiResource(
    normalizationContext: ['groups' => ['group:list']],
    denormalizationContext: ['groups' => ['group:create', 'group:update']],
    paginationItemsPerPage: 10,
    paginationClientItemsPerPage: true,
    operations: [
        new Get(
            normalizationContext: ['groups' => ['group:list', 'group:edit']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['group:list']]
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['name' => 'partial', 'roles' => 'exact'])]
class Group
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['group:list'])]
    private int $id;

    #[ORM\Column(length: 50, nullable: false)]
    #[Groups(['group:list', 'group:create', 'group:update'])]
    private string $name;

    #[ORM\Column(length: 50, nullable: false)]
    #[Groups(['group:list'])]
    #[Slug(fields: ['name'])]
    private string $slug;

    #[ORM\ManyToMany(targetEntity: Role::class)]
    #[ORM\JoinTable(name: "user_group_roles")]
    #[ORM\JoinColumn(name: "group_id")]
    #[ORM\InverseJoinColumn(name: "role_id")]
    #[Groups(['group:list', 'group:create', 'group:update'])]
    private Collection $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): Group
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string|null $slug
     * @return Group
     */
    public function setSlug(?string $slug): Group
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getRoles(): Collection
    {
        return $this->roles;
    }

    /**
     * @param Collection $roles
     * @return Group
     */
    public function setRoles(iterable $roles): Group
    {
        if ($roles instanceof Collection) {
            $this->roles = $roles;
        } else {
            $this->roles = new ArrayCollection(is_array($roles) ? $roles : iterator_to_array($roles));
        }

        return $this;
    }
}
