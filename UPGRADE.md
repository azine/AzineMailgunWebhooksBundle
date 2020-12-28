#Upgrade instructions

To upgrade from 2.0.0 to master, you'll need to migrate the your database structure & content.

You can create an auto-generated migration file with the command:

```bash
php bin/console doctrine:migration:diff
```

If you already have data in your database & you want to keep it, you'll need to do a few things manually:

1. move the SQL instruction to create the `on-delete-cascade`-constraint out of the generated migration.
2. create a second migration to update your db-content and generate the `mailgun_message_summary` entries from 
   you existing `mailgun_events`. See suggested code below.
3. create a third migration to add the `on-delete-cascade`-constraint you removed from the first migration in step 1.
4. run the migrations in this order.

```php
<?php

namespace Application\Migrations;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Update your db-table structure before applying this migration (doctrine:migration:diff && doctrine:migration:migrate).
 *
 * Important Note: doctrine:migration: diff will suggest to add a foreign key constraint like this:
 *                 "ALTER TABLE mailgun_event ADD CONSTRAINT FK_1271933F537A1329 FOREIGN KEY (message_id) REFERENCES mailgun_message_summary (id) ON DELETE CASCADE".
 *                 !!! BUT this constraint can only be added after the migration below ran successfully. !!!
 *
 * Add MessageSummary Entity to keep track and summarize what happened with a sent email.
 * Rename table email_traffic_statistics to mailgun_email_traffic_statistics
 */
class Version20201228090100 extends AbstractMigration implements ContainerAwareInterface
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
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function postUp(Schema $schema)
    {
        $repository = $this->manager->getRepository(MailgunMessageSummary::class);
        $processing = true;
        $page=0;
        $pageSize=100;
        while($processing) {
            $query = $this->manager->createQuery("select e from " . MailgunEvent::class . " e");
            $query->setFirstResult($page*$pageSize);
            $query->setMaxResults($pageSize);
            $iterableResult = $query->iterate();
            $counter = 0;
            foreach ($iterableResult as $row) {
                $repository->createOrUpdateMessageSummary($row[0]);
                $this->manager->flush();
                $counter++;
            }
            $this->manager->commit();
            $this->manager->clear();
            $processing = $counter == $pageSize;
            $this->manager->beginTransaction();
            $page++;
        }
    }

    /**
     * @param Schema $schema
     * @throws AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf('mysql' != $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');
    }
}

```
