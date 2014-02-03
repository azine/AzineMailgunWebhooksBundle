<?php
namespace Azine\MailgunWebhooksBundle\Controller;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Azine\MailgunWebhooksBundle\Entity\MailgunWebhookEvent;
use Azine\MailgunWebhooksBundle\Entity\Repositories\MailgunEventRepository;
use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;
use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;
use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

/**
 * MailgunEvent controller.
 *
 */
class MailgunEventController extends Controller
{

	/**
	 * Lists all MailgunEvent entities.
	 *
	 * @param Request $request
	 * @param integer $page
	 * @param integer $pageSize
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
	 */
	public function indexAction(Request $request, $page, $pageSize)	{
		$params = array();

		// get general filter options
		$params['filterOptions'] = array(
				'orderBy' => $this->getRepository()->getFieldsToOrderBy(),
				'eventTypes' => array_merge(array("all"), $this->getRepository()->getEventTypes()),
				'domains' => $this->getRepository()->getDomains(),
				'recipients' => $this->getRepository()->getRecipients(),
		);

		// get filter criteria from session
		$session = $request->getSession();
		$page = 		$session->get('page', $page);
		$pageSize =		$session->get('pageSize', $pageSize);
		$domain =		$session->get('domain', $params['filterOptions']['domains'][0]);
		$eventType =	$session->get('eventType', $params['filterOptions']['eventTypes'][0]);
		$search =		$session->get('search',"");
		$recipient =	$session->get('recipient',"");
		$orderBy =		$session->get('orderBy', 'timestamp');
		$orderDirection=$session->get('orderDirection', 'desc');

		// update filter criteria from get-request
		$page = 		$request->get('page', $page);
		$pageSize =		$request->get('pageSize', $pageSize);
		if($session->get('pageSize') != $pageSize){
			$page = 1;
		}
		if($request->get('clear')){
			$eventType = "all";
		} else {
			$eventType =	$request->get('eventType', $eventType);
		}

		// update filter criteria from post-request
		$filter = $request->get('filter');
		if(is_array($filter)){
			$domain =		$filter['domain'];
			$eventType =	$filter['eventType'];
			$search =		$filter['search'];
			$recipient =	$filter['recipient'];
			$orderBy =		$filter['orderBy'];
			$orderDirection =		$filter['orderDirection'];

		} else {
			$filter = array();
			$filter['domain'] =	$domain;
			$filter['eventType'] = $eventType;
			$filter['search'] = $search;
			$filter['recipient'] = $recipient;
			$filter['orderBy'] = $orderBy;
			$filter['orderDirection'] = $orderDirection;
		}

		// store filter criteria back to session
		$session->set('page', $page);
		$session->set('pageSize', $pageSize);
		$session->set('domain', $domain);
		$session->set('eventType', $eventType);
		$session->set('search',$search);
		$session->set('recipient',$recipient);
		$session->set('orderBy', $orderBy);
		$session->set('orderDirection', $orderDirection);


		// set params for filter-form
		$params['currentFilters'] = array(
					'domain' => $domain,
					'orderBy' => $orderBy,
					'orderDirection' => $orderDirection,
					'eventType' => $eventType,
					'pageSize' => $pageSize,
					'search' => $search,
					'recipient' => $recipient,
				);



		$eventCount = $this->getRepository()->getEventCount($filter);
		// validate the page/pageSize and with the total number of result entries
		if($eventCount > 0 && (($page -1 ) *$pageSize >= $eventCount)){
			$maxPage = max(1,ceil($eventCount / $pageSize));
			return $this->redirect($this->generateUrl("mailgunevent_list", array('page' => $maxPage, 'pageSize' => $pageSize))."?".$request->getQueryString());
		}

		// get the events
		$params['events'] = $this->getRepository()->getEvents($filter, array($orderBy => $orderDirection), $pageSize, ($page-1)*$pageSize);

		// set the params for the pager
		$params['paginatorParams'] = array(
					'paginationPath' => "mailgunevent_list",
					'pageSize' => $pageSize,
					'currentPage' => $page,
					'currentFilters' => $params['currentFilters'],
					'totalItems' => $eventCount,
					'lastPage' => ceil($eventCount/$pageSize),
					'showAlwaysFirstAndLast' => true,
				);

		return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:index.html.twig', $params);
	}

	/**
	 * Get the MailgunEvent Repository
	 * @return MailgunEventRepository
	 */
	private function getRepository(){
		return $this->getDoctrine()->getManager()->getRepository('AzineMailgunWebhooksBundle:MailgunEvent');
	}

	public function createFromWebhookAction(Request $request){
		$paramsPre = $request->request->all();
	   	$params = array_change_key_case($paramsPre, CASE_LOWER);

	   	if(sizeof($params) != sizeof($paramsPre)){
	   		$params['params_contained_duplicate_keys'] = $paramsPre;
	   	}


		// validate post-data
		$key = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX."_".AzineMailgunWebhooksExtension::API_KEY);
		$timestamp = $params['timestamp'];
		$token = $params['token'];
		$expectedSignature = hash_hmac("SHA256", $timestamp.$token, $key);
		if($expectedSignature != $params['signature']){
			return new Response("Signature verification failed.", 401);
		}

		// drop unused variables
		if(array_key_exists('x-mailgun-sid', $params)){
			unset($params['x-mailgun-sid']);
		}
		if(array_key_exists('attachment-count', $params)){
			unset($params['attachment-count']);
		}

		try {
			// create event & populate with supplied data
			$this->createEvent($params);

		} catch (\Exception $e) {
			$this->container->get('logger')->warn("AzineMailgunWebhooksBundle: creating entities failed: ".$e->getMessage());
			$this->container->get('logger')->warn($e->getTraceAsString());
			return new Response("AzineMailgunWebhooksBundle: creating entities failed: ".$e->getMessage(), 500);
		}

		// send response
		return new Response(print_r($params, true)."Thanx, for the info.", 200);

	}

	/**
	 * Map all params to MailgunEvent-fields and MailgunAttachments and MailgunCustomVariables
	 * @param EntityManager $params
	 */
	private function createEvent(array $params){
		$event = new MailgunEvent();

		// event
		if(array_key_exists('event', $params)){
			$event->setEvent($params['event']);
			unset($params['event']);
		}

		// domain
		if(array_key_exists('domain', $params)){
			$event->setDomain($params['domain']);
			unset($params['domain']);
		}
		// description
		if(array_key_exists('description', $params)){
			$event->setDescription($params['description']);
			unset($params['description']);
		}
		// notification	{
		if(array_key_exists('notification', $params)){
			$event->setNotification($params['notification']);
			unset($params['notification']);
		}
		// reason
		if(array_key_exists('reason', $params)){
			$event->setReason($params['reason']);
			unset($params['reason']);
		}
		// recipient
		if(array_key_exists('recipient', $params)){
			$event->setRecipient($params['recipient']);
			unset($params['recipient']);
		}
		// errorCode
		if(array_key_exists('code', $params)){
			$event->setErrorCode($params['code']);
			unset($params['code']);
		}
		// ip
		if(array_key_exists('ip', $params)){
			$event->setIp($params['ip']);
			unset($params['ip']);
		}
		// error
		if(array_key_exists('error', $params)){
			$event->setError($params['error']);
			unset($params['error']);
		}
		// country
		if(array_key_exists('country', $params)){
			$event->setCountry($params['country']);
			unset($params['country']);
		}
		// city
		if(array_key_exists('city', $params)){
			$event->setCity($params['city']);
			unset($params['city']);
		}
		// region
		if(array_key_exists('region', $params)){
			$event->setRegion($params['region']);
			unset($params['region']);
		}
		// campaignId
		if(array_key_exists('campaign-id', $params)){
			$event->setCampaignId($params['campaign-id']);
			unset($params['campaign-id']);
		}
		// campaignName	{
		if(array_key_exists('campaign-name', $params)){
			$event->setCampaignName($params['campaign-name']);
			unset($params['campaign-name']);
		}
		// clientName
		if(array_key_exists('client-name', $params)){
			$event->setClientName($params['client-name']);
			unset($params['client-name']);
		}
		// clientOs
		if(array_key_exists('client-os', $params)){
			$event->setClientOs($params['client-os']);
			unset($params['client-os']);
		}
		// clientType
		if(array_key_exists('client-type', $params)){
			$event->setClientType($params['client-type']);
			unset($params['client-type']);
		}
		// deviceType
		if(array_key_exists('device-type', $params)){
			$event->setDeviceType($params['device-type']);
			unset($params['device-type']);
		}
		// mailingList
		if(array_key_exists('mailing-list', $params)){
			$event->setmailingList($params['mailing-list']);
			unset($params['mailing-list']);
		}
		// messageHeaders
		if(array_key_exists('message-headers', $params)){
			$event->setMessageHeaders($params['message-headers']);
			unset($params['message-headers']);
		}
		// messageId
		if(array_key_exists('message-id', $params)){
			$event->setMessageId($params['message-id']);
			unset($params['message-id']);
		}
		// tag
		if(array_key_exists('tag', $params)){
			$event->setTag($params['tag']);
			unset($params['tag']);
		}
		// userAgent
		if(array_key_exists('user-agent', $params)){
			$event->setUserAgent($params['user-agent']);
			unset($params['user-agent']);
		}
		// url
		if(array_key_exists('url', $params)){
			$event->setUrl($params['url']);
			unset($params['url']);
		}
		// token
		if(array_key_exists('token', $params)){
			$event->setToken($params['token']);
			unset($params['token']);
		}
		// timestamp
	   if(array_key_exists('timestamp', $params)){
			$event->setTimestamp($params['timestamp']);
			unset($params['timestamp']);
		}
		// signature
		if(array_key_exists('signature', $params)){
			$event->setSignature($params['signature']);
			unset($params['signature']);
		}

		$manager = $this->container->get('doctrine.orm.entity_manager');
		$manager->persist($event);

		// process the remaining posted values
		foreach($params as $key => $value){

			if(strpos($key, "attachment-") === 0 ){
				// create event attachments
				$attachment = new MailgunAttachment();
				$attachment->setEvent($event);
				$attachment->setCounter(substr($key,11));

				// get the file
				if($value instanceof UploadedFile){}
				$file = $value;

				$attachment->setContent(file_get_contents($file->getPathname()));
				$attachment->setSize($file->getSize());
				$attachment->setType($file->getMimeType());
				$attachment->setName($file->getFilename());

				$manager->persist($attachment);

			} else {
				// create custom-variables for event
				$customVar = new MailgunCustomVariable();
				$customVar->setEvent($event);
				$customVar->setVariableName($key);
				$customVar->setContent($value);

				$manager->persist($customVar);
			}

		}

		// save all entities
		$manager->flush();

		// Dispatch an event about the logging of a Webhook-call
		$this->get("event_dispatcher")->dispatch(MailgunEvent::CREATE_EVENT, new MailgunWebhookEvent($event));
	}

	/**
	 * Finds and displays a MailgunEvent entity.
	 *
	 */
	public function showAction($id)
	{
		$em = $this->getDoctrine()->getManager();

		$entity = $em->getRepository('AzineMailgunWebhooksBundle:MailgunEvent')->find($id);

		if (!$entity) {
			throw $this->createNotFoundException('Unable to find MailgunEvent entity.');
		}

		return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:show.html.twig', array(
			'entity'	  => $entity,
		));
	}

	/**
	 * Deletes a MailgunEvent entity.
	 *
	 */
	public function deleteAction(Request $request) {

		$id = $request->get('eventId');

		$em = $this->getDoctrine()->getManager();
		$entity = $em->getRepository('AzineMailgunWebhooksBundle:MailgunEvent')->find($id);

		if (!$entity) {
			throw $this->createNotFoundException('Unable to find MailgunEvent entity.');
		}

		$em->remove($entity);
		$em->flush();

		if($request->isXmlHttpRequest()){
			return new JsonResponse(array("success" => true));
		}

		$session = $request->getSession();
		$page = 		$session->get('page', 1);
		$pageSize =		$session->get('pageSize', 25);
		return $this->redirect($this->generateUrl('mailgunevent_list', array('page' => $page, 'pageSize' => $pageSize)));
	}

}
