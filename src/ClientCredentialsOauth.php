<?php
namespace leoding86\BaiduService;

class ClientCredentialsOauth
{
    const ACCESS_TOKEN_URL = 'https://openapi.baidu.com/oauth/2.0/token';

    private $nowTime;
    private $cacheObject;

    private $savedPath;
    private $savedName;
    private $savedFile;
    private $grantType;
    private $clientId;
    private $clientSecret;
    private $accessTokenRaw;
    private $accessTokenFormat;
    private $accessToken;
    private $codeUrl;
    private $codeRaw;

    /**
     * 构造函数
     * @param string $saved_path    缓存token的路径
     * @param string $saved_name    缓存token的文件名，不要扩展名
     * @param string $client_id     应用的ID
     * @param string $client_secret 应用的secret
     * @param string $grant_type    授权类型
     */
    public function __construct(
        ICacheObject $cache_object,
        $client_id,
        $client_secret,
        $grant_type = 'client_credentials'
    ) {
        $this->nowTime = time();
        $this->setCacheObject($cache_object);
        $this->setClientId($client_id);
        $this->setClientSecret($client_secret);
        $this->setGrantType($grant_type);
    }

    /**
     * 设置授权类型
     * @param string $grant_type 授权类型
     */
    private function setGrantType($grant_type)
    {
        $this->grantType = $grant_type;
    }

    /**
     * 设置应用ID
     * @param string $client_id 应用ID
     */
    private function setClientId($client_id)
    {
        $this->clientId = $client_id;
    }

    /**
     * 设置应用secret
     * @param string $client_secret 应用secret
     */
    private function setClientSecret($client_secret)
    {
        $this->clientSecret = $client_secret;
    }

    /**
     * 设置缓存操作对象
     * @param ICacheObject $cache_object 缓存操作对象
     */
    public function setCacheObject(ICacheObject $cache_object)
    {
        $this->cacheObject = $cache_object;
    }

    /**
     * 获得原始token字符串
     * @return string 原始token字符串
     */
    public function getAccessTokenRaw()
    {
        return $this->accessTokenRaw;
    }

    /**
     * 获得格式化后的token数据
     * @return array 格式化后token
     */
    public function getAccessTokenFormat()
    {
        return $this->accessTokenFormat;
    }

    /**
     * 获得Access token
     * @return string
     */
    // public function getAccessToken()
    // {
    //     return $this->accessToken;
    // }

    /**
     * 请求access token，并赋值属性
     * @return boolean true为成功，false为失败
     */
    private function sendRequest()
    {
        $request_url = self::ACCESS_TOKEN_URL . '?'
                     . 'grant_type=' . $this->grantType . '&'
                     . 'client_id=' . $this->clientId . '&'
                     . 'client_secret=' . $this->clientSecret;

        $access_token_raw = file_get_contents($request_url);

        try {
            $access_token_format = json_decode($access_token_raw, true);

            if (isset($access_token_format['access_token'])) {
                $access_token_format['expires_time'] = $access_token_format['expires_in'] + $this->nowTime;
                $this->accessTokenFormat =& $access_token_format;
                $this->accessTokenRaw = json_encode($access_token_format);
                // $this->accessToken = $access_token_format['access_token'];
                $this->cacheObject->cacheAccessToken($this->accessTokenRaw);
                return true;
            }
        }
        catch (Exception $e) { }

        return false;
    }

    /**
     * 缓存token到文件
     * @return void
     */
    private function cacheAccessToken()
    {
        $saved_file_handle = fopen($this->savedPath, 'w');
        fwrite($saved_file_handle, $this->accessTokenRaw);
        fclose($saved_file_handle);
    }

    /**
     * 读取缓存的token信息
     * @return boolean ture为成功，false为失败
     */
    public function readAccessToken()
    {
        // $this->buildSavedAccessToken();

        // if (is_file($this->savedPath)) {
        // $access_token_raw = file_get_contents($this->savedPath);
        $access_token_raw = $this->cacheObject->getAccessToken();

        try {
            $access_token_format = json_decode($access_token_raw, true);
            /* 判断token有效期 */
            if (isset($access_token_format['expires_time']) && $access_token_format['expires_time'] >= $this->nowTime) {
                $this->accessTokenRaw = $access_token_raw;
                $this->accessTokenFormat = $access_token_format;
                // $this->accessToken = $access_token_format['access_token'];
                return true;
            }

        } catch (Exception $e) { }
        // }

        return $this->sendRequest();
    }

}