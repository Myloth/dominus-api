<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260829202410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE event_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE quest_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE event (id INT NOT NULL, name VARCHAR(255) NOT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, active BOOLEAN DEFAULT false NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN event.start_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.end_date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN event.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE quest (id INT NOT NULL, pre_req_quest_id INT DEFAULT NULL, event_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, niv_guilde_req INT DEFAULT NULL, objectives JSON NOT NULL, rewards JSON NOT NULL, unlock_lore TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_4317F817A3AD94FC ON quest (pre_req_quest_id)');
        $this->addSql('CREATE INDEX IDX_4317F81771F7E88B ON quest (event_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_QUEST_NAME ON quest (name)');
        $this->addSql('COMMENT ON COLUMN quest.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE quests_tags (quest_id INT NOT NULL, tag_id INT NOT NULL, PRIMARY KEY(quest_id, tag_id))');
        $this->addSql('CREATE INDEX IDX_114AB8EC209E9EF4 ON quests_tags (quest_id)');
        $this->addSql('CREATE INDEX IDX_114AB8ECBAD26311 ON quests_tags (tag_id)');
        $this->addSql('ALTER TABLE quest ADD CONSTRAINT FK_4317F817A3AD94FC FOREIGN KEY (pre_req_quest_id) REFERENCES quest (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quest ADD CONSTRAINT FK_4317F81771F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quests_tags ADD CONSTRAINT FK_114AB8EC209E9EF4 FOREIGN KEY (quest_id) REFERENCES quest (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE quests_tags ADD CONSTRAINT FK_114AB8ECBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE event_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE quest_id_seq CASCADE');
        $this->addSql('ALTER TABLE quest DROP CONSTRAINT FK_4317F817A3AD94FC');
        $this->addSql('ALTER TABLE quest DROP CONSTRAINT FK_4317F81771F7E88B');
        $this->addSql('ALTER TABLE quests_tags DROP CONSTRAINT FK_114AB8EC209E9EF4');
        $this->addSql('ALTER TABLE quests_tags DROP CONSTRAINT FK_114AB8ECBAD26311');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE quest');
        $this->addSql('DROP TABLE quests_tags');
    }
}
