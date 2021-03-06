<?php

declare(strict_types=1);

namespace Neucore\Service;

use Doctrine\Common\Persistence\ObjectManagerDecorator;
use Neucore\Log\Context;
use Psr\Log\LoggerInterface;

class ObjectManager extends ObjectManagerDecorator
{
    /**
     * @var LoggerInterface
     */
    private $log;

    public function __construct(\Doctrine\Persistence\ObjectManager $wrapped, LoggerInterface $log)
    {
        $this->wrapped = $wrapped;
        $this->log = $log;
    }

    /**
     * @return bool
     */
    public function flush(): bool
    {
        try {
            parent::flush();
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage(), [Context::EXCEPTION => $e]);
            return false;
        }

        return true;
    }
}
