<?php namespace Prettus\RequestLogger\Helpers;

use Carbon\Carbon;

/**
 * Class RequestInterpolation
 * @package Prettus\RequestLogger\Helpers
 */
class RequestInterpolation extends BaseInterpolation {

    /**
     * @param string $text
     * @return string
     */
    public function interpolate($text)
    {

        $variables = explode(" ",$text);

        foreach( $variables as $variable ) {
            $matches = [];
            preg_match("/{\\s*(.+?)\\s*}(\\r?\\n)?/", $variable, $matches);
            if( isset($matches[1]) ) {
                $value = $this->resolveVariable($matches[0], $matches[1]);

                $flags = $value['flags'];
                $value = $value['value'];

                if (!in_array('ne', $flags))
                    // escape value
                    $value = $this->escape($value);

                $text = str_replace($matches[0], $value, $text);
            }
        }

        return $text;
    }

    /**
     * @param $raw
     * @param $variable
     * @return array
     */
    public function resolveVariable($raw, $variable)
    {
        $flags = explode(':', $variable);
        // return variable name without flags
        $variable = array_shift($flags);

        $method = str_replace([
            "remoteAddr",
            "scheme",
            "port",
            "queryString",
            "remoteUser",
            'body'
        ], [
            "ip",
            "getScheme",
            "getPort",
            "getQueryString",
            "getUser",
            "getContent"
        ],camel_case($variable));

        $server_var = str_replace([
            "ACCEPT",
            "ACCEPT_CHARSET",
            "ACCEPT_ENCODING",
            "ACCEPT_LANGUAGE",
            "HOST",
            "REFERER",
            "USER_AGENT",
        ], [
            "HTTP_ACCEPT",
            "HTTP_ACCEPT_CHARSET",
            "HTTP_ACCEPT_ENCODING",
            "HTTP_ACCEPT_LANGUAGE",
            "HTTP_HOST",
            "HTTP_REFERER",
            "HTTP_USER_AGENT"
        ], strtoupper(str_replace("-","_", $variable)) );

        if( method_exists($this->request, $method) ) {
            return ['value' => $this->request->$method(), 'flags' => $flags];
        } elseif( isset($_SERVER[$server_var]) ) {
            return ['value' => $this->request->server($server_var), 'flags' => $flags];
        } else {
            $matches = [];
            preg_match("/([-\\w]{2,})(?:\\[([^\\]]+)\\])?/", $variable, $matches);

            if( count($matches) == 2 ) {
                switch($matches[0]) {
                case "date":
                    $matches[] = "clf";
                    break;

                case "referer":
                    $matches[1] = 'header';
                    $matches[] = 'referer';
                    break;

                case "req-all":
                    $matches[] = 'dummy';
                    break;
                }
            }

            if( is_array($matches) && count($matches) == 3 ) {
                list($line, $var, $option) = $matches;

                switch(strtolower($var)) {
                    case "date":

                        $formats = [
                            "clf"=>Carbon::now()->format("d/M/Y:H:i:s O"),
                            "iso"=>Carbon::now()->toIso8601String(),
                            "web"=>Carbon::now()->toRfc1123String()
                        ];

                        return ['value' => isset($formats[$option]) ? $formats[$option] :
                            Carbon::now()->format($option), 'flags' => $flags];

                    case "req":
                    case "header":
                        return ['value' => $this->request->header(strtolower($option)), 'flags' => $flags];
                    case "server":
                        return ['value' => $this->request->server($option), 'flags' => $flags];
                    case "input":
                        return ['value' => $this->request->input($option), 'flags' => $flags];
                    case "req-all":
                        return ['value' => json_encode($this->request->all(), JSON_UNESCAPED_UNICODE), 'flags' => $flags];
                    default;
                        return ['value' => $raw, 'flags' => $flags];
                }
            }
        }

        return ['value' => $raw, 'flags' => $flags];
    }
}
