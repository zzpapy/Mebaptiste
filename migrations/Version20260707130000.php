<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute le champ show_in_menu sur la table page';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page ADD show_in_menu BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE page DROP show_in_menu');
    }
}
