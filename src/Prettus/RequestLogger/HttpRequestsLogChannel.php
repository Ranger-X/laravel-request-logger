<?php

namespace Prettus\RequestLogger;

use Exception;
use Illuminate\Log\LogManager;
use Monolog\Handler\RavenHandler;
use Monolog\Logger;

class HttpRequestsLogChannel extends LogManager
{
    /**
     * @param array $config
     *
     * @return Logger
     */
    public function __invoke(array $config)
    {
        $channel = $this->parseChannel($config); //$config['name'] ?? env('APP_ENV');
        $monolog = new Logger($channel);

        if( config('request-logger.logger.enabled') && $handlers = config('request-logger.logger.handlers') ) {
            if( count($handlers) ) {
                //Remove default laravel handler
                $monolog->popHandler();

                foreach($handlers as $handler) {
                    if( class_exists($handler) ) {
                        $monolog->pushHandler(app($handler));
                    } else {
                        throw new Exception("Handler class [{$handler}] does not exist");
                    }
                }
            }
        }

        return $monolog;
    }
}