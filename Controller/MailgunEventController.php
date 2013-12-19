<?php

namespace Azine\MailgunWebhooksBundle\Controller;

use Azine\MailgunWebhooksBundle\Entity\MailgunCustomVariable;

use Azine\MailgunWebhooksBundle\Entity\MailgunAttachment;

use Azine\MailgunWebhooksBundle\DependencyInjection\AzineMailgunWebhooksExtension;

use Symfony\Component\HttpFoundation\Response;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Azine\MailgunWebhooksBundle\Entity\MailgunEvent;

use Azine\MailgunWebhooksBundle\Form\MailgunEventType;

/**
 * MailgunEvent controller.
 *
 */
class MailgunEventController extends Controller
{

	/**
	 * Lists all MailgunEvent entities.
	 *
	 */
	public function indexAction()
	{
		$em = $this->getDoctrine()->getManager();

		$entities = $em->getRepository('AzineMailgunWebhooksBundle:MailgunEvent')->findAll();

		return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:index.html.twig', array(
			'entities' => $entities,
		));
	}

	public function createFromWebhookAction(Request $request){
	   	$params = $request->request->all();

		// validate post-data
		$key = $this->container->getParameter(AzineMailgunWebhooksExtension::PREFIX."_".AzineMailgunWebhooksExtension::API_KEY);
		$timestamp = $params['timestamp'];
		$token = $params['token'];
		$expectedSignature = hash_hmac("SHA256", $timestamp.$token, $key);
		if($expectedSignature != $params['signature']){
			return new Response("Signature verification failed.", 401);
		}

		// drop unused variables
		if(array_key_exists('X-Mailgun-Sid', $params)){
			unset($params['X-Mailgun-Sid']);
		}
		if(array_key_exists('attachment-count', $params)){
			unset($params['attachment-count']);
		}

		// create event & populate with supplied data
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
		if(array_key_exists('Message-Id', $params)){
			$event->setMessageId($params['Message-Id']);
			unset($params['Message-Id']);
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

				//
				$attachment->setContent($content);
				$attachment->setSize($size);
				$attachment->setType($type);
				$attachment->setName($name);

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
		try {
			$manager->flush();
		} catch (\Exception $e) {
			$this->container->get('logger')->warn("AzineMailgunWebhooksBundle: creating entities failed: ".$e->getMessage());
			$this->container->get('logger')->warn($e->getTraceAsString());
			return new Response(print_r($params), 500);
		}

		// send response
		return new Response(print_r($params)."Thanx, for the info.", 200);
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

		$deleteForm = $this->createDeleteForm($id);

		return $this->render('AzineMailgunWebhooksBundle:MailgunEvent:show.html.twig', array(
			'entity'	  => $entity,
			'delete_form' => $deleteForm->createView(),
		));
	}

	/**
	 * Deletes a MailgunEvent entity.
	 *
	 */
	public function deleteAction(Request $request, $id)
	{
		$form = $this->createDeleteForm($id);
		$form->handleRequest($request);

		if ($form->isValid()) {
			$em = $this->getDoctrine()->getManager();
			$entity = $em->getRepository('AzineMailgunWebhooksBundle:MailgunEvent')->find($id);

			if (!$entity) {
				throw $this->createNotFoundException('Unable to find MailgunEvent entity.');
			}

			$em->remove($entity);
			$em->flush();
		}

		return $this->redirect($this->generateUrl('mailgunevent'));
	}

	/**
	 * Creates a form to delete a MailgunEvent entity by id.
	 *
	 * @param mixed $id The entity id
	 *
	 * @return \Symfony\Component\Form\Form The form
	 */
	private function createDeleteForm($id)
	{
		return $this->createFormBuilder()
			->setAction($this->generateUrl('mailgunevent_delete', array('id' => $id)))
			->setMethod('DELETE')
			->add('submit', 'submit', array('label' => 'Delete'))
			->getForm()
		;
	}
}
