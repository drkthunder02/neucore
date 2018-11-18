<?php declare(strict_types=1);

namespace Brave\Core\Migrations;

use Brave\Core\Entity\SystemVariable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181118133330 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO system_variables (name, value, scope) VALUES ("'.SystemVariable::MAIL_ACCOUNT_DISABLED_SUBJECT.'", "", "settings")');
        $this->addSql('INSERT INTO system_variables (name, value, scope) VALUES ("'.SystemVariable::MAIL_ACCOUNT_DISABLED_BODY.'", "", "settings")');
        $this->addSql('INSERT INTO system_variables (name, value, scope) VALUES ("'.SystemVariable::MAIL_ACCOUNT_DISABLED_ACTIVE.'", "", "settings")');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM system_variables WHERE name = "'.SystemVariable::MAIL_ACCOUNT_DISABLED_SUBJECT.'"');
        $this->addSql('DELETE FROM system_variables WHERE name = "'.SystemVariable::MAIL_ACCOUNT_DISABLED_BODY.'"');
        $this->addSql('DELETE FROM system_variables WHERE name = "'.SystemVariable::MAIL_ACCOUNT_DISABLED_ACTIVE.'"');
    }
}
