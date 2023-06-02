<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230601045308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE telegram_event (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(20) NOT NULL, note TEXT NOT NULL, roles LONGTEXT DEFAULT \'[]\' NOT NULL COMMENT \'(DC2Type:json)\', UNIQUE INDEX name (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_message (id INT AUTO_INCREMENT NOT NULL, update_id INT NOT NULL, message_id INT NOT NULL, user_chat_id BIGINT NOT NULL, date DATETIME NOT NULL, datein DATETIME NOT NULL, mess TEXT NOT NULL, entities TEXT NOT NULL COMMENT \'json\', INDEX user_chat_id (user_chat_id), INDEX datein (datein), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_save (id INT AUTO_INCREMENT NOT NULL, datein DATETIME NOT NULL, ident VARCHAR(30) NOT NULL, user_chat_id BIGINT NOT NULL, message_id INT NOT NULL, mess TEXT NOT NULL, INDEX ident (ident), INDEX datein (datein), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_user (id INT AUTO_INCREMENT NOT NULL, datein DATETIME NOT NULL, user_chat_id BIGINT NOT NULL, is_bot TINYINT(1) NOT NULL, first_name VARCHAR(30) NOT NULL, username VARCHAR(30) NOT NULL, language_code VARCHAR(2) NOT NULL, superuser TINYINT(1) NOT NULL, phone VARCHAR(12) NOT NULL, id_user BIGINT DEFAULT NULL, INDEX chats (user_chat_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE telegram_user_event (id INT AUTO_INCREMENT NOT NULL, datein DATETIME NOT NULL, user_chat_id BIGINT NOT NULL, name VARCHAR(20) NOT NULL, active TINYINT(1) NOT NULL, INDEX user_chat_id (user_chat_id), INDEX code (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE telegram_event');
        $this->addSql('DROP TABLE telegram_message');
        $this->addSql('DROP TABLE telegram_save');
        $this->addSql('DROP TABLE telegram_user');
        $this->addSql('DROP TABLE telegram_user_event');
    }
}
