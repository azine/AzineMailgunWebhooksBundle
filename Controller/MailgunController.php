<?php

namespace Azine\MailgunWebhooksBundle\Controller;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
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
        $params['events'] = $this->getRepository()->getEventCount(array());
        $params['bounced'] = $this->getRepository()->getEventCount(array('eventType' => 'bounced'));
        $params['dropped'] = $this->getRepository()->getEventCount(array('eventType' => 'dropped'));
        $params['complained'] = $this->getRepository()->getEventCount(array('eventType' => 'complained'));
        $params['unsubscribed'] = $this->getRepository()->getEventCount(array('eventType' => 'unsubscribed'));
        $params['unopened'] = $this->getRepository()->getEventCount(array('eventType' => 'unopened'));
        $params['emailWebViewRoute'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE);
        $params['emailWebViewToken'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

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
