<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912160226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make task.project_id non-nullable';
    }

    public function up(Schema $schema): void
    {
        // On cible la bonne clé : celle du project_id
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        
        // On effectue le changement sur la colonne project_id
        $this->addSql('ALTER TABLE task CHANGE project_id project_id INT NOT NULL');
        
        // On recrée la clé étrangère pour project_id
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }

    public function down(Schema $schema): void
    {
        // On cible la bonne clé : celle du project_id
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        
        // On annule le changement sur la colonne project_id en la rendant nullable à nouveau
        $this->addSql('ALTER TABLE task CHANGE project_id project_id INT DEFAULT NULL');
        
        // On recrée la clé étrangère pour project_id
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
    }
}