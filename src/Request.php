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

    /**
     * 构造方法
     * @param string $url           请求链接
     * @param string $method        请求类型
     * @param string $params        请求参数
     * @param string $need_encoding 是否对$params进行url encoding
     */
    public function __construct($url = null, $method = 'get', $params = array(), $need_encoding = false)
    {
        $this->asynRequests = array();
        $this->response = array();
        $this->error = null;
        $this->setUrl($url);
        $this->setParams($params);
        $this->setMethod($method);
        $this->needEncoding($need_encoding);
    }

    /**
     * 设置请求链接
     * @param string $url 请求链接
     */
    public function setUrl($url)
    {
        if ($url !== null) {
            $this->url = $url;
        }
    }

    /**
     * 设置请求参数
     * @param  array  $params 请求参数
     */
    public function setParams(array $params)
    {
        $this->params = $params;
    }

    /**
     * 设置请求类型
     * @param string $method 请求类型，如POST，GET等
     */
    public function setMethod($method = 'get')
    {
        $this->method = $method;
    }

    /**
     * 设置是否需要对请求参数url encode
     * @param  boolean $needEncoding true为编码，false为不编码
     */
    public function needEncoding($needEncoding)
    {
        $this->needEncoding = (boolean)$needEncoding;
    }

    /**
     * 获得请求响应
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * 获得请求响应正文
     * @return string
     */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
     * 获得请求响应头
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->responseHeaders;
    }

    /**
     * 设置异步请求链接以及参数
     * @param array $asyn_requests [['url' => array, 'options' => array], ...]
     */
    public function setAsynRequests(array $asyn_requests)
    {
        $this->asynRequests = $asyn_requests;
    }

    /**
     * 获得异步请求响应集
     * @return array
     */
    public function getAsynResponses()
    {
        return $this->asynResponses;
    }

    /**
     * 发送请求并返回结果
     * @param  array $options 额外的参数
     * @return void
     */
    public function sendRequest($options = array())
    {
        if (!$this->url) {
            throw new \Exception("Url is not a valid url", 1);
        }

        $ch = curl_init($this->url);
        // 设置curl
        $this->setCurlOptions($ch, $options);
        // 执行curl
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception($error, 1);
        }
        else {
            $this->headerInfo = curl_getinfo($ch);
            curl_close($ch);
            $this->response = $response;
            $this->responseBody = $this->parseResultBody($this->response);
            $this->responseHeaders = $this->parseResultHeaders($this->response);
        }
    }

    /**
     * 发送异步请求
     * @return void
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
            $mrc = curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        unset($running);

        if ($mrc > 0) {
            throw new \Exception(curl_multi_strerror($mrc), 1);
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

        foreach (explode("\r\n", $header_text) as $line) {
            if (preg_match('/\s(\d+)\s/', $line, $match)) {
                $headers['http_code'] = $match[1];
            }
            else if (!empty($line)) {
                list($key, $value) = explode(': ', $line);
                $headers[strtolower($key)] = $value;
            }
        }

        return $headers;
    }

}