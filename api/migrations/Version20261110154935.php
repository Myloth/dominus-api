<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enum\EntityTypeEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261110154935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create EntityType table, seed default values from EntityTypeEnum, and create ManyToMany relation with Tag';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE entity_type_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE entity_type (id INT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ENTITY_TYPE_SLUG ON entity_type (slug)');

        foreach (EntityTypeEnum::cases() as $entityType) {
            $this->addSql(
                "INSERT INTO entity_type (id, name, slug) VALUES (nextval('entity_type_id_seq'), :name, :slug)",
                [
                    'name' => $entityType->label(),
                    'slug' => $entityType->value,
                ]
            );
        }

        $this->addSql('CREATE TABLE tags_entity_types (tag_id INT NOT NULL, entity_type_id INT NOT NULL, PRIMARY KEY(tag_id, entity_type_id))');
        $this->addSql('CREATE INDEX IDX_A6A04092BAD26311 ON tags_entity_types (tag_id)');
        $this->addSql('CREATE INDEX IDX_A6A040925681BEB0 ON tags_entity_types (entity_type_id)');
        $this->addSql('ALTER TABLE tags_entity_types ADD CONSTRAINT FK_A6A04092BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tags_entity_types ADD CONSTRAINT FK_A6A040925681BEB0 FOREIGN KEY (entity_type_id) REFERENCES entity_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('INSERT INTO tags_entity_types (tag_id, entity_type_id) SELECT t.id, et.id FROM tag t INNER JOIN entity_type et ON t.entity_type = et.slug');
        $this->addSql('ALTER TABLE tag DROP entity_type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE tag ADD entity_type VARCHAR(255) DEFAULT 'quest' NOT NULL");
        $this->addSql('ALTER TABLE tag ALTER COLUMN entity_type DROP DEFAULT');
        $this->addSql('DROP SEQUENCE entity_type_id_seq CASCADE');
        $this->addSql('ALTER TABLE tags_entity_types DROP CONSTRAINT FK_A6A04092BAD26311');
        $this->addSql('ALTER TABLE tags_entity_types DROP CONSTRAINT FK_A6A040925681BEB0');
        $this->addSql('DROP TABLE entity_type');
        $this->addSql('DROP TABLE tags_entity_types');
    }
}
