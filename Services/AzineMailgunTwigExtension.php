<?php
namespace Azine\MailgunWebhooksBundle\Services;

class AzineMailgunTwigExtension extends \Twig_Extension
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'printArray' => new  \Twig_SimpleFilter('printArray', array($this, 'printArray'), array('is_safe' => array('html'))),
        );
    }

    /**
     *
     * @param  array $var
     * @return mixed
     */
    public static function printArray(array $var)
    {
        return var_dump($var);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'azine_mailgun_webhooks_bundle_twig_extension';
    }
}
