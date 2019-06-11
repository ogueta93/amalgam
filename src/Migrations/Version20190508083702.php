<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190508083702 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_card CHANGE id_user id_user INT DEFAULT NULL, CHANGE id_card id_card INT DEFAULT NULL');
        $this->addSql('ALTER TABLE card CHANGE type_id type_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_battle CHANGE user_id user_id INT DEFAULT NULL, CHANGE battle_id battle_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE battle CHANGE battle_status_id battle_status_id INT DEFAULT NULL, CHANGE battle_type_id battle_type_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX nickname ON user');
        $this->addSql('ALTER TABLE user ADD roles JSON NOT NULL, DROP name, DROP last_name, DROP age, DROP nickname, DROP created_at, DROP updated_at, DROP deleted_at');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE battle CHANGE battle_status_id battle_status_id INT NOT NULL, CHANGE battle_type_id battle_type_id INT NOT NULL');
        $this->addSql('ALTER TABLE card CHANGE type_id type_id INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD name VARCHAR(255) NOT NULL COLLATE utf8mb4_spanish_ci, ADD last_name VARCHAR(255) NOT NULL COLLATE utf8mb4_spanish_ci, ADD age DATE NOT NULL, ADD nickname VARCHAR(25) NOT NULL COLLATE utf8mb4_spanish_ci, ADD created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD updated_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, ADD deleted_at DATETIME DEFAULT \'NULL\', DROP roles');
        $this->addSql('CREATE UNIQUE INDEX nickname ON user (nickname)');
        $this->addSql('ALTER TABLE user_battle CHANGE battle_id battle_id INT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_card CHANGE id_card id_card INT NOT NULL, CHANGE id_user id_user INT NOT NULL');
    }
}
