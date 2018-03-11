<?php

namespace Currency\Captain\Traits;

use Psr\Log\LoggerInterface;

/**
 * Trait HasLogger
 * @package Currency\Captain\Traits
 */
trait HasLogger
{
    /** @var  LoggerInterface */
    protected $logger = null;

    /**
     * @return LoggerInterface
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
