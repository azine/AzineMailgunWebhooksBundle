<?php
namespace Azine\MailgunWebhooksBundle\Command;

use Azine\MailgunWebhooksBundle\Services\AzineMailgunService;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Delete old MailgunEvent entries from the database
 *
 * @author dominik
 *
 */
class DeleteOldEntriesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('mailgun:delete-events')
            ->setDescription('Delete old mailgun events from the database')
            ->setDefinition(array(
                                    new InputArgument(	'date',
                                                        InputArgument::OPTIONAL,
                                                        'Delete Mailgun Events that are older than "date" (Default: 60 days ago). The date must be something that strtotime() is able to parse:  => e.g. "since yesterday", "until 2 days ago", "> now - 2 hours", ">= 2005-10-15" '
                                                    ),
                                    new InputArgument(	'type',
                                                        InputArgument::OPTIONAL,
                                                        'Delete Mailgun Events of the given type. It no type is supplied, events of all types are deleted.'
                                                    ),
                            ))
            ->setHelp(<<<EOF
The <info>mailgun:delete-events</info> command deletes old mailgun_event entries from the database.

Possible types are:

Event 			Description
---------------------------------------------------------------------
accepted 		Mailgun accepted the request to send/forward the email and the message has been placed in queue.
rejected 		Mailgun rejected the request to send/forward the email.
delivered 		Mailgun sent the email and it was accepted by the recipient email server.
failed 			Mailgun could not deliver the email to the recipient email server.
opened 			The email recipient opened the email and enabled image viewing. Open tracking must be enabled in the Mailgun control panel, and the CNAME record must be pointing to mailgun.org.
clicked 		The email recipient clicked on a link in the email. Click tracking must be enabled in the Mailgun control panel, and the CNAME record must be pointing to mailgun.org.
unsubscribed 	The email recipient clicked on the unsubscribe link. Unsubscribe tracking must be enabled in the Mailgun control panel.
complained 		The email recipient clicked on the spam complaint button within their email client. Feedback loops enable the notification to be received by Mailgun.
stored 			Mailgun has stored an incoming message
EOF
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument("type");
        $ageLimit = $input->getArgument("date");

        if ($type == null || $type == "") {
            $output->write("deleting entries of any type.", true);
            $typeDesc = "any type";
        } elseif (array_search($type, array("accepted", "rejected", "delivered", "failed", "opened", "clicked", "unsubscribed", "complained", "stored"))) {
            $typeDesc = "type '$type'";
        } else {
            throw new InvalidArgumentException("Unknown type: $type");
        }

        if ($ageLimit == null || $ageLimit == "") {
            $output->write("using default age-limit of '60 days ago'.", true);
            $ageLimit = "60 days ago";
        }

        $date = new \DateTime($ageLimit);

        $result = $this->getMailgunService()->removeEvents($type, $date);

        $output->write("All MailgunEvents (& their CustomVariables & Attachments) older than ".$date->format("Y-m-d H:i:s")." of $typeDesc have been deleted ($result).", true);
    }

    /**
     * @return AzineMailgunService
     */
    private function getMailgunService()
    {
        return $this->getContainer()->get('azine_mailgun.service');
    }
}
