<?php

namespace Application\Migrations;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Move & rename this migration to your doctrine:migrations directory and run it to upgrade the
 * db tables and generate the MessageSummary-Objects from the MailgunEvents in your database.
 *
 * Add MessageSummary Entity to keep track and summarize what happened with a sent email.
 * Rename table email_traffic_statistics to mailgun_email_traffic_statistics
 */
class Version20201123090099 extends AbstractMigration implements ContainerAwareInterface
{
    /** @var EntityManager */
    private $manager;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->manager = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf('mysql' != $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('RENAME TABLE email_traffic_statistics TO mailgun_email_traffic_statistics');
        $this->addSql('CREATE TABLE mailgun_message_summary (id VARCHAR(255) NOT NULL, fromAddress VARCHAR(255) NOT NULL, toAddress LONGTEXT NOT NULL, firstOpened DATETIME DEFAULT NULL, lastOpened DATETIME DEFAULT NULL, openCount INT NOT NULL, sendDate DATETIME NOT NULL, deliveryStatus VARCHAR(95) NOT NULL, senderIp VARCHAR(15) NOT NULL, subject LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE mailgun_event ADD messageId VARCHAR(255) DEFAULT NULL, CHANGE message_id message_id VARCHAR(255) NOT NULL, ADD sender VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE mailgun_event ADD CONSTRAINT FK_1271933FA4C3A0DA FOREIGN KEY (messageId) REFERENCES mailgun_message_summary (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_1271933FA4C3A0DA ON mailgun_event (messageId)');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postUp(Schema $schema)
    {
        foreach($this->manager->getRepository(MailgunEvent::class)->findAll() as $event){
            $this->manager->getRepository(MailgunMessageSummary::class)->createOrUpdateMessageSummary($event);
            $this->manager->flush();
        }
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' != $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
        $this->addSql('RENAME TABLE mailgun_email_traffic_statistics TO email_traffic_statistics');
        $this->addSql('DROP INDEX IDX_1271933FA4C3A0DA ON mailgun_event');
        $this->addSql('ALTER TABLE mailgun_event DROP FOREIGN KEY FK_1271933FA4C3A0DA');
        $this->addSql('ALTER TABLE mailgun_event DROP sender, DROP messageId, CHANGE message_id message_id VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('DROP TABLE mailgun_message_summary');
    }
}
