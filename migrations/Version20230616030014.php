<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230616030014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE log_sms (id INT AUTO_INCREMENT NOT NULL, id_user INT NOT NULL, datein DATETIME NOT NULL, phone VARCHAR(12) NOT NULL, action VARCHAR(20) NOT NULL, code VARCHAR(10) NOT NULL, mess VARCHAR(255) NOT NULL, try TINYINT(1) NOT NULL, ip VARCHAR(50) NOT NULL, sended TINYINT(1) NOT NULL, result TEXT NOT NULL, result_code INT NOT NULL, INDEX phone (phone), INDEX datein (datein), INDEX ip (ip), INDEX result_code (result_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE log_sms');
    }
}
