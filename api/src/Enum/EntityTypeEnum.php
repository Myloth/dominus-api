<?php

namespace App\Enum;

enum EntityTypeEnum: string
{
    case QUEST = 'quest';
    case PNJ = 'pnj';
    case ITEM = 'item';
    case GEAR = 'gear';
    case DUNGEON = 'dungeon';
    case GUILD = 'guild';

    public function label(): string
    {
        return match ($this) {
            self::QUEST => 'Quête',
            self::PNJ => 'PNJ',
            self::ITEM => 'Objet',
            self::GEAR => 'Équipement',
            self::DUNGEON => 'Donjon',
            self::GUILD => 'Guilde',
        };
    }
}
