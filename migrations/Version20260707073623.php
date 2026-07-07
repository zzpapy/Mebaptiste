<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260707073623 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Renomme consultation_type en consultation (préserve les données)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT fk_fe38f844804f7d71');
        $this->addSql('ALTER TABLE consultation_type RENAME TO consultation');
        $this->addSql('ALTER TABLE appointment RENAME COLUMN consultation_type_id TO consultation_id');
        $this->addSql('ALTER INDEX idx_fe38f844804f7d71 RENAME TO idx_fe38f84462ff6cdf');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F84462FF6CDF FOREIGN KEY (consultation_id) REFERENCES consultation (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE appointment DROP CONSTRAINT FK_FE38F84462FF6CDF');
        $this->addSql('ALTER TABLE consultation RENAME TO consultation_type');
        $this->addSql('ALTER TABLE appointment RENAME COLUMN consultation_id TO consultation_type_id');
        $this->addSql('ALTER INDEX idx_fe38f84462ff6cdf RENAME TO idx_fe38f844804f7d71');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT fk_fe38f844804f7d71 FOREIGN KEY (consultation_type_id) REFERENCES consultation_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}