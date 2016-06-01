<?php
namespace leoding86\BaiduService;

class Request {

    private $url;
    private $asynRequests;
    private $params;
    private $method;
    private $needEncoding;
    private $response;
    private $responseBody;
    private $responseHeaders;
    private $asynResponses;
    private $hasHeader;
    private $headerInfo;
    private $error;

    public function __construct($url = null, $method = null, $params = null, $need_encoding = null)
    {
        $this->asynRequests = array();
        $this->method = 'get';
        $this->response = array();
        $this->error = null;
        $this->setUrl($url);
        $this->params($params);
        $this->method($method);
        $this->needEncoding($need_encoding);
    }

    public function setUrl($url)
    {
        if ($url !== null) {
            $this->url = $url;
        }
    }

    public function setAsynRequests($asynRequests)
    {
        $this->asynRequests = $asynRequests;
    }

    public function params($params)
    {
        $this->params = is_array($params) ? $params : array();
    }

    public function method($method)
    {
        $this->method = $method;
    }

    public function needEncoding($needEncoding)
    {
        $this->needEncoding = $needEncoding;
    }

    public function getResult()
    {
        return $this->response;
    }

    public function getResultBody()
    {
        return $this->responseBody;
    }

    public function getResultHeaders()
    {
        return $this->responseHeaders;
    }

    public function getAsynResponses()
    {
        return $this->asynResponses;
    }

    public function error()
    {
        return $this->error;
    }

    /**
     * 发送请求并返回结果
     * @param  array $options 额外的参数
     * @return bool
     */
    public function sendRequest($options = array())
    {
        $ch = curl_init($this->url);
        // 设置curl
        $this->setCurlOptions($ch, $options);
        // 执行curl
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            curl_close($ch);
            $this->error = 'E1';
            return false;
        }
        else {
            $this->headerInfo = curl_getinfo($ch);
            curl_close($ch);
            $this->response = $response;
            $this->responseBody = $this->parseResultBody($this->response);
            $this->responseHeaders = $this->parseResultHeaders($this->response);
            return true;
        }
    }

    /**
     * 发送异步请求
     * @return bool
     */
    public function sendRequestAsyn()
    {
        $mh = curl_multi_init();
        $ch_handles = array();
        // 遍历设置的连接
        foreach ($this->asynRequests as $request) {
            $ch = curl_init($request['url']);
            $this->setCurlOptions($ch, $request['options']);
            $ch_handles[] = $ch;
            // 添加$ch到$mh
            curl_multi_add_handle($mh, $ch);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh)
        } while ($running > 0);
        unset($running);

        if ($mrc > 0) {
            $this->error = curl_multi_strerror($mrc);
            return false;
        }

        foreach ($ch_handles as $key => $ch) {
            $this->headerInfo = curl_getinfo($ch);
            $this->asynResponses[$key] = new \stdClass();
            $this->asynResponses[$key]->response = curl_multi_getcontent($ch);
            $this->asynResponses[$key]->responseBody = $this->parseResultBody($this->asynResponses[$key]->response);
            $this->asynResponses[$key]->responseHeaders = $this->parseResultHeaders($this->asynResponses[$key]->response);
            curl_multi_remove_handle($mh, $ch);
        }

        curl_multi_close($mh);

        return true;
    }

    /**
     * 设置curl
     * @return void
     */
    private function setCurlOptions(&$ch, &$options)
    {
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (strtolower($this->method) === 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->params);
        }
        else {
            foreach ($this->params as $name => $value) {
                if (is_string($value)) {
                    if (strpos($url, '?') === false) {
                        $url .= '?' . ($this->needEncoding ? ($name.'='.urlencode($value)) : ($name.'='.$value));
                    }
                    else {
                        $url .= '&' . ($this->needEncoding ? ($name.'='.urlencode($value)) : ($name.'='.$value));
                    }
                }
            }
        }

        if (!empty($options)) {
            curl_setopt_array($ch, $options);
            if (isset($options[CURLOPT_HEADER]) and $options[CURLOPT_HEADER]) {
                $this->hasHeader = true;
            }
        }
    }

    private function parseResultBody($response)
    {
        if ($this->hasHeader) {
            return substr($response, $this->headerInfo['header_size']);
        }
        else {
            return $response;
        }
    }

    private function parseResultHeaders($response)
    {
        if ($this->hasHeader) {
            $header_text = substr($response, 0, $this->headerInfo['header_size']);
            return $this->getHeadersArray($header_text);
        }
        else {
            return null;
        }

    }

    private function getHeadersArray($header_text)
    {
        $headers = array();

        foreach (explode("\r\n", $header_text) as $i => $line) {
            if ($i === 0) {
                $headers['http_code'] = $line;
            }
            else if (!empty($line)) {
                list ($key, $value) = explode(': ', $line);
                $headers[strtolower($key)] = $value;
            }
        }

        return $headers;
    }

}