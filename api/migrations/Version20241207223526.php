<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241207223526 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_user_groups (user_id INT NOT NULL, group_id INT NOT NULL, PRIMARY KEY(user_id, group_id))');
        $this->addSql('CREATE INDEX IDX_3C24EBD8A76ED395 ON user_user_groups (user_id)');
        $this->addSql('CREATE INDEX IDX_3C24EBD8FE54D947 ON user_user_groups (group_id)');
        $this->addSql('CREATE TABLE user_group_roles (group_id INT NOT NULL, role_id INT NOT NULL, PRIMARY KEY(group_id, role_id))');
        $this->addSql('CREATE INDEX IDX_4F09CC35FE54D947 ON user_group_roles (group_id)');
        $this->addSql('CREATE INDEX IDX_4F09CC35D60322AC ON user_group_roles (role_id)');
        $this->addSql('ALTER TABLE user_user_groups ADD CONSTRAINT FK_3C24EBD8A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_user_groups ADD CONSTRAINT FK_3C24EBD8FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_roles ADD CONSTRAINT FK_4F09CC35FE54D947 FOREIGN KEY (group_id) REFERENCES user_group (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_group_roles ADD CONSTRAINT FK_4F09CC35D60322AC FOREIGN KEY (role_id) REFERENCES user_role (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE user_user_groups DROP CONSTRAINT FK_3C24EBD8A76ED395');
        $this->addSql('ALTER TABLE user_user_groups DROP CONSTRAINT FK_3C24EBD8FE54D947');
        $this->addSql('ALTER TABLE user_group_roles DROP CONSTRAINT FK_4F09CC35FE54D947');
        $this->addSql('ALTER TABLE user_group_roles DROP CONSTRAINT FK_4F09CC35D60322AC');
        $this->addSql('DROP TABLE user_user_groups');
        $this->addSql('DROP TABLE user_group_roles');
    }
}
