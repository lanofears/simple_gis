<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Создание первоначальной схемы каталога
 */
class Version20160325182527 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE SCHEMA gis_catalog AUTHORIZATION '.$this->connection->getUsername());

        $this->addSql('CREATE SEQUENCE gis_catalog.building_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE gis_catalog.organization_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE gis_catalog.rubric_id_seq INCREMENT BY 1 MINVALUE 1 START 1');

        $this->addSql('CREATE TABLE gis_catalog.building (
            id INT NOT NULL,
            address VARCHAR(255) NOT NULL,
            longitude NUMERIC(10, 8) NOT NULL,
            latitude NUMERIC(10, 8) NOT NULL,
            PRIMARY KEY(id)
        )');

        $this->addSql('CREATE TABLE gis_catalog.organization (
            id INT NOT NULL,
            building_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            phones VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_organization_building ON gis_catalog.organization (building_id)');
        $this->addSql('COMMENT ON COLUMN gis_catalog.organization.phones IS \'(DC2Type:array)\'');

        $this->addSql('CREATE TABLE gis_catalog.organization_rubrics (
            organization_id INT NOT NULL,
            rubric_id INT NOT NULL,
            PRIMARY KEY(organization_id, rubric_id)
        )');
        $this->addSql('CREATE INDEX idx_or_organization ON gis_catalog.organization_rubrics (organization_id)');
        $this->addSql('CREATE INDEX idx_or_rubric ON gis_catalog.organization_rubrics (rubric_id)');

        $this->addSql('CREATE TABLE gis_catalog.rubric (
            id INT NOT NULL,
            parent_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_rubric_parent ON gis_catalog.rubric (parent_id)');

        $this->addSql('ALTER TABLE gis_catalog.organization ADD CONSTRAINT fk_organization_building
            FOREIGN KEY (building_id) REFERENCES gis_catalog.building (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gis_catalog.organization_rubrics ADD CONSTRAINT fk_or_organization
            FOREIGN KEY (organization_id) REFERENCES gis_catalog.organization (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gis_catalog.organization_rubrics ADD CONSTRAINT fk_or_rubric
            FOREIGN KEY (rubric_id) REFERENCES gis_catalog.rubric (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gis_catalog.rubric ADD CONSTRAINT fk_rubric_parent
            FOREIGN KEY (parent_id) REFERENCES gis_catalog.rubric (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE gis_catalog.organization DROP CONSTRAINT fk_organization_building');
        $this->addSql('ALTER TABLE gis_catalog.organization_rubrics DROP CONSTRAINT fk_or_organization');
        $this->addSql('ALTER TABLE gis_catalog.organization_rubrics DROP CONSTRAINT fk_or_rubric');
        $this->addSql('ALTER TABLE gis_catalog.rubric DROP CONSTRAINT fk_rubric_parent');
        $this->addSql('DROP SEQUENCE gis_catalog.building_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE gis_catalog.organization_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE gis_catalog.rubric_id_seq CASCADE');
        $this->addSql('DROP TABLE gis_catalog.building');
        $this->addSql('DROP TABLE gis_catalog.organization');
        $this->addSql('DROP TABLE gis_catalog.organization_rubrics');
        $this->addSql('DROP TABLE gis_catalog.rubric');

        $this->addSql('DROP SCHEMA gis_catalog');
    }
}
