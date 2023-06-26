<?php

namespace Azine\MailgunWebhooksBundle\Services;

use Twig\Extension\AbstractExtension;

/**
 * Class AzineMailgunTwigExtension
 * Provides some filters and global variables.
 */
class AzineMailgunTwigExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
            'printArray' => new \Twig_SimpleFilter('printArray', array($this, 'printArray'), array('is_safe' => array('html'))),
        );
    }

    /**
     * @param $vars
     * @param false $allDetails
     * @param string $indent
     * @param int $maxRecursionDepth
     * @return string
     */
    public static function printArray($vars, $allDetails = false, $indent = '', $maxRecursionDepth = 3)
    {
        $output = '';
        $defaultIndent = '    ';
        if(is_object($vars)) {
            $className = get_class($vars);
            $vars = (array) $vars;
            $object = array();
            foreach ($vars as $key => $value){
                $key = substr($key, strlen($className)+2);
                $object[$key] = $value;
            }
            $vars = array($className => $object);
        }
        ksort($vars);
        foreach ($vars as $key => $value) {
            if ($maxRecursionDepth > 0 && $allDetails && (is_array($value) || is_object($value)) && $value !== $vars) { // avoid infinite recursion
                $value = "\n".self::printArray((array) $value, $allDetails, $indent.$defaultIndent, $maxRecursionDepth - 1);
            } else {
                if (is_array($value)) {
                    $value = 'array('.sizeof($value).')';
                } elseif (is_object($value)) {
                    $value = 'object('.get_class($value).')';
                } else {
                    $value = htmlentities($value);
                }
            }
            $output .= $indent."$key: $value\n";
        }

        return $output;
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
