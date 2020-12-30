<?php

namespace Azine\MailgunWebhooksBundle\Controller;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;
use Azine\MailgunWebhooksBundle\Entity\MailgunMessageSummary;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunMessageSummaryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mailgun controller.
 */
class MailgunController extends AbstractController
{
    /**
     * Show MailgunEvent-Overview.
     */
    public function eventOverviewAction()
    {
        $eventRepository = $this->getEventRepository();
        $params = array();
        $params['importantEvents'] = $eventRepository->getImportantEvents(10);
        $params['events'] = $eventRepository->getEventCount(array());
        $params['bounced'] = $eventRepository->getEventCount(array('eventType' => 'bounced'));
        $params['dropped'] = $eventRepository->getEventCount(array('eventType' => 'dropped'));
        $params['complained'] = $eventRepository->getEventCount(array('eventType' => 'complained'));
        $params['unsubscribed'] = $eventRepository->getEventCount(array('eventType' => 'unsubscribed'));
        $params['unopened'] = $eventRepository->getEventCount(array('eventType' => 'unopened'));
        $params['emailWebViewRoute'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE);
        $params['emailWebViewToken'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

        return $this->render('AzineMailgunWebhooksBundle::overview.html.twig', $params);
    }

    /**
     * Lists all MailgunEvent entities.
     *
     * @param Request $request
     * @param int     $page
     * @param int     $pageSize
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function eventIndexAction(Request $request, $page, $pageSize)
    {
        $params = array();
        $eventRepository = $this->getEventRepository();
        // get general filter options
        $params['filterOptions'] = array(
            'orderBy' => $eventRepository->getFieldsToOrderBy(),
            'eventTypes' => $eventRepository->getEventTypes(),
            'domains' => $eventRepository->getDomains(),
            'recipients' => $eventRepository->getRecipients(),
        );

        if ($request->get('clear')) {
            $request->getSession()->remove('mailgunEventIndexParams');
        }

        // get filter criteria from session
        $sessionParams = $request->getSession()->get('mailgunEventIndexParams', array(
            'page' => 1,
            'pageSize' => 25,
            'domain' => '',
            'eventType' => 'all',
            'search' => '',
            'recipient' => '',
            'orderBy' => 'timestamp',
            'orderDirection' => 'desc',
        ));

        $page = $sessionParams['page'];
        $pageSize = $sessionParams['pageSize'];
        $domain = $sessionParams['domain'];
        $eventType = $sessionParams['eventType'];
        $search = $sessionParams['search'];
        $recipient = $sessionParams['recipient'];
        $orderBy = $sessionParams['orderBy'];
        $orderDirection = $sessionParams['orderDirection'];

        // update filter criteria from get-request
        if ($request->isMethod('GET')) {
            $page = $request->get('page', $sessionParams['page']);
            $pageSize = $request->get('pageSize', $sessionParams['pageSize']);
            if ($sessionParams['pageSize'] != $pageSize) {
                $page = 1;
            }
            $eventType = $request->get('eventType', 'all');

        // update filter criteria from post-request
        } elseif ($request->isMethod('POST') && is_array($request->get('filter'))) {
            $filter = $request->get('filter');
            $eventType = $filter['eventType'];
            $domain = $filter['domain'];
            $search = trim($filter['search']);
            $recipient = trim($filter['recipient']);
            $orderBy = $filter['orderBy'];
            $orderDirection = $filter['orderDirection'];
        }

        // set params for filter-form
        $currentFilter = array(
                'page' => $page,
                'pageSize' => $pageSize,
                'domain' => $domain,
                'eventType' => $eventType,
                'search' => $search,
                'recipient' => $recipient,
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            );
        // store filter criteria back to session & to params
        $request->getSession()->set('mailgunEventIndexParams', $currentFilter);
        $params['currentFilters'] = $currentFilter;

        $eventCount = $eventRepository->getEventCount($currentFilter);
        // validate the page/pageSize and with the total number of result entries
        if ($eventCount > 0 && (($page - 1) * $pageSize >= $eventCount)) {
            $maxPage = max(1, ceil($eventCount / $pageSize));

            return $this->redirect($this->generateUrl('mailgunevent_list', array('page' => $maxPage, 'pageSize' => $pageSize)).'?'.$request->getQueryString());
        }

        // get the events
        $params['events'] = $eventRepository->getEvents($currentFilter, array($orderBy => $orderDirection), $pageSize, ($page - 1) * $pageSize);
        $params['emailWebViewRoute'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE);
        $params['emailWebViewToken'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

        // set the params for the pager
        $params['paginatorParams'] = array(
            'paginationPath' => 'mailgunevent_list',
            'pageSize' => $pageSize,
            'currentPage' => $page,
            'currentFilters' => $currentFilter,
            'totalItems' => $eventCount,
            'lastPage' => ceil($eventCount / $pageSize),
            'showAlwaysFirstAndLast' => true,
        );

        return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:index.html.twig', $params);
    }

    /**
     * Finds and displays a MailgunEvent entity.
     */
    public function eventShowAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AzineMailgunWebhooksBundle:MailgunEvent')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MailgunEvent entity.');
        }

        return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:show.html.twig', array(
            'emailWebViewRoute' => $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE),
            'emailWebViewToken' => $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN),
            'entity' => $entity,
        ));
    }

    /**
     * Deletes a MailgunEvent entity.
     */
    public function eventDeleteAction(Request $request)
    {
        $entity = $this->getEventRepository()->find($request->get('eventId'));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find MailgunEvent entity.');
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($entity);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(array('success' => true));
        }

        $session = $request->getSession();
        $page = $session->get('page', 1);
        $pageSize = $session->get('pageSize', 25);

        return $this->redirect($this->generateUrl('mailgunevent_list', array('page' => $page, 'pageSize' => $pageSize)));
    }

    /**
     * @param Request $request
     * @param $page
     * @param $pageSize
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|null
     */
    public function messageSummaryIndexAction(Request $request, $page, $pageSize)
    {
        $params = array();

        $messageSummaryRepository = $this->getMessageSummaryRepository();

        // get general filter options
        $params['filterOptions'] = array(
            'orderBy' => $messageSummaryRepository->getFieldsToOrderBy(),
            'toAddress' => $messageSummaryRepository->getRecipients(),
            'fromAddress' => $messageSummaryRepository->getSenders(),
        );

        if ($request->get('clear')) {
            $request->getSession()->remove('mailgunMessageIndexParams');
        }

        // get filter criteria from session
        $sessionParams = $request->getSession()->get('mailgunMessageIndexParams', array(
            'page' => 1,
            'pageSize' => 25,
            'search' => '',
            'toAddress' => '',
            'fromAddress' => '',
            'orderBy' => 'sendDate',
            'orderDirection' => 'desc',
        ));

        $page = $sessionParams['page'];
        $pageSize = $sessionParams['pageSize'];
        $search = $sessionParams['search'];
        $toAddress = $sessionParams['toAddress'];
        $fromAddress = $sessionParams['fromAddress'];
        $orderBy = $sessionParams['orderBy'];
        $orderDirection = $sessionParams['orderDirection'];

        // update filter criteria from get-request
        if ($request->isMethod('GET')) {
            $page = $request->get('page', $sessionParams['page']);
            $pageSize = $request->get('pageSize', $sessionParams['pageSize']);
            if ($sessionParams['pageSize'] != $pageSize) {
                $page = 1;
            }

            // update filter criteria from post-request
        } elseif ($request->isMethod('POST') && is_array($request->get('filter'))) {
            $filter = $request->get('filter');
            $search = trim($filter['search']);
            $toAddress = trim($filter['toAddress']);
            $fromAddress = trim($filter['fromAddress']);
            $orderBy = $filter['orderBy'];
            $orderDirection = $filter['orderDirection'];
        }

        // set params for filter-form
        $currentFilter = array(
            'page' => $page,
            'pageSize' => $pageSize,
            'search' => $search,
            'toAddress' => $toAddress,
            'fromAddress' => $fromAddress,
            'orderBy' => $orderBy,
            'orderDirection' => $orderDirection,
        );
        // store filter criteria back to session & to params
        $request->getSession()->set('mailgunMessageIndexParams', $currentFilter);
        $params['currentFilters'] = $currentFilter;

        $messageSummaryCount = $messageSummaryRepository->getMessageSummaryCount($currentFilter);
        // validate the page/pageSize and with the total number of result entries
        if ($messageSummaryCount > 0 && (($page - 1) * $pageSize >= $messageSummaryCount)) {
            $maxPage = max(1, ceil($messageSummaryCount / $pageSize));

            return $this->redirect($this->generateUrl('mailgun_message_summary_list', array('page' => $maxPage, 'pageSize' => $pageSize)).'?'.$request->getQueryString());
        }

        // get the events
        $params['messageSummaries'] = $messageSummaryRepository->getMessageSummaries($currentFilter, array($orderBy => $orderDirection), $pageSize, ($page - 1) * $pageSize);
        $params['emailWebViewRoute'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE);
        $params['emailWebViewToken'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

        // set the params for the pager
        $params['paginatorParams'] = array(
            'paginationPath' => 'mailgun_message_summary_list',
            'pageSize' => $pageSize,
            'currentPage' => $page,
            'currentFilters' => $currentFilter,
            'totalItems' => $messageSummaryCount,
            'lastPage' => ceil($messageSummaryCount / $pageSize),
            'showAlwaysFirstAndLast' => true,
        );

        return $this->render('AzineMailgunWebhooksBundle::messageSummaryIndex.html.twig', $params);
    }

    public function messageSummaryShowAction(Request $request, MailgunMessageSummary $messageSummary)
    {
        $params = array('messageSummary' => $messageSummary);
        $params['emailWebViewRoute'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_ROUTE);
        $params['emailWebViewToken'] = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX.'_'.AzineMailgunWebhooksExtension::WEB_VIEW_TOKEN);

        return $this->render('AzineMailgunWebhooksBundle::messageSummaryShow.html.twig', $params);
    }

    public function cockpitAction()
    {
        return $this->render('@AzineMailgunWebhooks/cockpit.html.twig', $this->get('azine_mailgun.cockpit_service')->getCockpitDataAsArray());
    }

    /**
     * Get the MailgunMessageSummary Repository.
     *
     * @return MailgunMessageSummaryRepository
     */
    private function getMessageSummaryRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository(MailgunMessageSummary::class);
    }

    /**
     * Get the MailgunEvent Repository.
     *
     * @return MailgunEventRepository
     */
    private function getEventRepository()
    {
        return $this->getDoctrine()->getManager()->getRepository(MailgunEvent::class);
    }

    public function getMessageSummaryAction(Request $request)
    {
        $fromAddress = '';
        $toAddress = '';
        $sendTime = '';
        $subject = '';
        $summary = $this->getMessageSummaryRepository()->findSummary($fromAddress, $toAddress, $sendTime, $subject);

        return new JsonResponse($summary);
    }
}
