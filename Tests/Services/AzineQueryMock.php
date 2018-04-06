<?php

namespace Azine\MailgunWebhooksBundle\Tests\Services;

use Doctrine\ORM\AbstractQuery;

/**
 * @author Dominik Businger
 */
class AzineQueryMock extends AbstractQuery
{
    // @codeCoverageIgnoreStart
    private $result;

    public function __construct($result)
    {
        $this->result = $result;
    }

    protected function _doExecute()
    {
        return $this->result;
    }

    public function execute($parameters = null, $hydrationMode = null)
    {
        return $this->_doExecute();
    }

    public function getSQL()
    {
        return 'dummy sql';
    }
}
