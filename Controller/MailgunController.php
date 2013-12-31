<?php
namespace Azine\MailgunWebhooksBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
/**
 * Mailgun controller.
 *
 */

class MailgunController extends Controller
{

	/**
	 * Show Mailgun-Overview
	 *
	 */
	public function overviewAction()	{
		return $this->render('AzineMailgunWebhooksBundle::overview.html.twig');
	}

}
