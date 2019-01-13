<?php

namespace Azine\MailgunWebhooksBundle\Controller;

use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Mailgun controller.
 */
class MailgunController extends AbstractController
{
    /**
     * Show Mailgun-Overview.
     */
    public function overviewAction()
    {
        $params = array();
        $params['importantEvents'] = $this->getRepository()->getImportantEvents(10);
        $params['events'] = sizeof($this->getRepository()->findAll());
        $params['bounced'] = sizeof($this->getRepository()->findBy(array('event' => 'bounced')));
        $params['dropped'] = sizeof($this->getRepository()->findBy(array('event' => 'dropped')));
        $params['complained'] = sizeof($this->getRepository()->findBy(array('event' => 'complained')));
        $params['unsubscribed'] = sizeof($this->getRepository()->findBy(array('event' => 'unsubscribed')));
        $params['unopened'] = $this->getRepository()->getEventCount(array('eventType' => 'unopened'));

        return $this->render('AzineMailgunWebhooksBundle::overview.html.twig', $params);
    }

    public function cockpitAction()
    {
        return $this->render('@AzineMailgunWebhooks/cockpit.html.twig', $this->get('azine_mailgun.cockpit_service')->getCockpitDataAsArray());
    }

    /**
     * Get the MailgunEvent Repository.
     *
     * @return MailgunEventRepository
     */
    private function getRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
    }
}
