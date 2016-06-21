<?php
namespace leoding86\BaiduService;

class CacheObject implements ICacheObject
{
    const DS = DIRECTORY_SEPARATOR;

    private $savePath;

    public function __construct($save_dir, $save_name)
    {
        $this->buildSavedAccessToken($save_dir, $save_name);
    }

    /**
     * 构建缓存token的文件路径
     * @return string 缓存token的文件路径
     */
    private function buildSavedAccessToken($save_dir, $save_name)
    {
        if (is_dir($save_dir)) {
            $this->savePath = $save_dir . self::DS . $save_name . '.php';
            if (!is_file($this->savePath)) {
                file_put_contents($this->savePath, '');
            }
        }
    }

    public function cacheAccessToken($access_token)
    {
        $encoded_token = base64_encode($access_token);
        $cache_content = <<<EOT
<?php
return array(
    'encoded_token' => '{$encoded_token}',
);
EOT;
        file_put_contents($this->savePath, $cache_content);
    }

    public function getAccessToken()
    {
        $encoded_token = include($this->savePath);
        return base64_decode($encoded_token['encoded_token']);
    }
}