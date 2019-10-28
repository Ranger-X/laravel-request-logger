<?php

namespace Prettus\RequestLogger;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Prettus\RequestLogger\Helpers\RequestInterpolation;
use Prettus\RequestLogger\Helpers\ResponseInterpolation;
use Log;

/**
 * Class Logger
 * @package Prettus\Logger\Request
 */
class ResponseLogger
{
    /**
     *
     */
    const LOG_CONTEXT = "RESPONSE";

    /**
     * @var array
     */
    protected $formats = [
        "combined"  =>'{remote-addr} - {remote-user} [{date}] "{method} {url} HTTP/{http-version}" {status} {content-length} "{referer}" "{user-agent}"',
        "common"    =>'{remote-addr} - {remote-user} [{date}] "{method} {url} HTTP/{http-version}" {status} {content-length}',
        "dev"       =>'{method} {url} {status} {response-time} ms - {content-length}',
        "short"     =>'{remote-addr} {remote-user} {method} {url} HTTP/{http-version} {status} {content-length} - {response-time} ms',
        "tiny"      =>'{method} {url} {status} {content-length} - {response-time} ms'
    ];

    /**
     * @var RequestInterpolation
     */
    protected $requestInterpolation;

    /**
     * @var ResponseInterpolation
     */
    protected $responseInterpolation;
    
    public function __construct(RequestInterpolation $requestInterpolation, ResponseInterpolation $responseInterpolation)
    {
        $this->requestInterpolation = $requestInterpolation;
        $this->responseInterpolation = $responseInterpolation;
    }

    /**
     * @param Request $request
     * @param Response $response
     */
    public function log(Request $request, Response $response)
    {
        //throw new \LogicException(print_r($request, true));

        $this->responseInterpolation->setResponse($response);
        $this->responseInterpolation->setRequest($request);

        $this->requestInterpolation->setRequest($request);

        $format = config('logging.channels.requests_log.format', "{ip} {remote_user} {date} {method} {url} HTTP/{http_version} {status} {content_length} {referer} {user_agent}");
        $format = Arr::get($this->formats, $format, $format);
        $message = $this->responseInterpolation->interpolate($format);
        $message = $this->requestInterpolation->interpolate($message);

        Log::channel('requests_log')->log(config('logging.channels.requests_log.level', 'info') , $message, [
            static::LOG_CONTEXT
        ]);
    }

}
