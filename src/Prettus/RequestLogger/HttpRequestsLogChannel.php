<?php

namespace Prettus\RequestLogger;

use Exception;
use Illuminate\Log\LogManager;
use Monolog\Logger as Monolog;
use Prettus\RequestLogger\Handler\HttpLoggerHandler;

class HttpRequestsLogChannel extends LogManager
{
    /**
     * @param array $config
     *
     * @return Monolog
     */
    public function __invoke(array $config)
    {
        return new Monolog($this->parseChannel($config), [
            $this->prepareHandler(new HttpLoggerHandler(
                $config['path'],
                $config['days'] ?? 0,
                $this->level($config),
                $config['bubble'] ?? true,
                $config['permission'] ?? null,
                $config['locking'] ?? false
            )),
        ]);
    }
}