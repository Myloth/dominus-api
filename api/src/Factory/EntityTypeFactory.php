<?php

namespace App\Factory;

use App\Entity\EntityType;
use App\Enum\EntityTypeEnum;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<EntityType>
 */
final class EntityTypeFactory extends PersistentObjectFactory
{
    public function __construct()
    {
    }

    #[\Override]
    public static function class(): string
    {
        return EntityType::class;
    }

    public static function createSpecific(): array
    {
        $entityTypes = [];
        foreach (EntityTypeEnum::cases() as $entityTypeEnum) {
            $entityTypes[] = self::findOrCreate([
                'name' => $entityTypeEnum->label(),
                'slug' => $entityTypeEnum->value,
            ]);
        }

        return $entityTypes;
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->word(),
            'slug' => self::faker()->unique()->slug(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }
}
