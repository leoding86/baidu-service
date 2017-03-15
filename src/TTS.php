<?php
namespace leoding86\BaiduService;

class TTS
{
    const API_URL = 'http://tsn.baidu.com/text2audio';
    const TEXT_LIMIT = 255; /* 256 - 1  */

    private $error;
    private $enableCache;
    private $cacheRoot;
    private $cacheName;

    private $tex;
    private $lan;
    private $tok;
    private $ctp;
    private $cuid;
    private $spd;
    private $pit;
    private $vol;
    private $per;

    /**
     * 构造实例
     * @param string  $tok          访问令牌
     * @param string  $cache_root   缓存目录
     */
    public function __construct($tok, $cache_root = null)
    {
        try {
            if ($cache_root) {
                $this->enableCache(true);
                $this->setCacheRoot($cache_root);
            }
            else {
                $this->enableCache(false);
            }

            $this->setTok($tok);
            $this->setLan('zh');
            $this->setCtp(1);
            $this->setSpd(5);
            $this->setPit(5);
            $this->setVol(5);
            $this->setPer(0);
        }
        catch (Exception $e) {
            $this->error = $e->getMessage();
        }
    }

    /**
     * 检查文件名有效性
     * 
     * @param  array $files 文件列表
     * @return bool
     */
    static private function checkFilesVaild($files)
    {
        if (empty($files)) return false;

        $counter = '1';
        foreach ($files as $file) {
            list($filename, $ext) = explode('.', $file);

            if ($ext !== 'mp3') {
                return false;
            }

            if ($counter != $filename) {
                return false;
            }
            $counter++;
        }
        return true;
    }

    /**
     * 构造POST字符串
     * 
     * @param  array $params 一唯数组
     * @return string $body
     */
    private function buildPostBody($params)
    {
        $tmp = array();
        foreach ($params as $name => $value) {
            $tmp[] = $name . '=' . $value;
        }
        return implode('&', $tmp);
    }

    /**
     * 保存语音文件
     * @param  string $data            语音数据
     * @param  string $audio_cache_dir 缓存语音目录
     * @param  string $filename        保存的文件名
     * @return void
     */
    private function cacheAudio($data, $audio_cache_dir, $filename)
    {
        if (!is_dir($audio_cache_dir)) {
            if (!mkdir($audio_cache_dir)) {
                throw new \Exception("Cannot create cache directory of audio. The target directory is '" . $audio_cache_dir . "'", 1);
            }
        }
        file_put_contents($audio_cache_dir . '/' . $filename, $data);
    }

    /**
     * 两次转化链接参数
     * @param  string $string 链接参数值
     * @return string         转化以后的值
     */
    private function doubleUrlencode($string)
    {
        return rawurlencode(rawurlencode($string));
    }

    /**
     * 获得最近一次错误信息
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 设置是否可以缓存语音
     * @param boolean $enable
     */
    public function enableCache($enable)
    {
        $this->enableCache = (bool)$enable;
    }

    /**
     * 设置缓存语音的根路径
     * @param string $dir 有效目录
     */
    public function setCacheRoot($dir)
    {
        $this->cacheRoot = $dir;
        if (!is_dir($this->cacheRoot)) {
            throw new \Exception("The directory of root of cache is not exists", 1);
        }
    }

    /**
     * 设置需要合成的文本
     * @param string $tex 需要合成的文本
     */
    public function setTex($tex)
    {
        $this->tex = $tex;
    }

    /**
     * 设置语言参数
     * @param string $lan 语言参数
     */
    public function setLan($lan = 'zh')
    {
        $this->lan = 'zh';
    }

    /**
     * 设置访问令牌
     * @param string $tok 访问令牌
     */
    public function setTok($tok)
    {
        $this->tok = $tok;
    }

    /**
     * 设置客户端类型
     * @param int $ctp 恒等于1，为web端
     */
    public function setCtp($ctp = 1)
    {
        $this->ctp = 1;
    }

    /**
     * 设置用户唯一标识
     * @param string $cuid 用户标识
     */
    public function setCuid($cuid)
    {
        $this->cuid = $cuid;
    }

    /**
     * 设置语速
     * @param integer $spd 0-9为有效值
     */
    public function setSpd($spd = 5)
    {
        if ($spd < 0 || $spd > 9) {
            throw new \Exception('Argument \'Spd\' must be between 0 to 9');
        }
        $this->spd = (int)$spd;
    }

    /**
     * 设置语调
     * @param integer $pit 0-9为有效值
     */
    public function setPit($pit = 5)
    {
        if ($pit < 0 || $pit > 9) {
            throw new  \Exception('Argument \'pit\' must be between 0 to 9');
        }
        $this->pit = (int)$pit;
    }

    /**
     * 设置音量
     * @param integer $vol 0-9为有效值
     */
    public function setVol($vol = 5)
    {
        if ($vol < 0 || $vol > 9) {
            throw new \Exception('Argument \'vol\' must be between 0 to 9');
        }
        $this->vol = (int)$vol;
    }

    /**
     * 设置朗读性别
     * @param int $per 0为女声，1为男声
     */
    public function setPer($per = 0)
    {
        switch ($per) {
            case 0:
            case 1:
                $this->per = $per;
                break;
            
            default:
                throw new \Exception('Argument \'per\' must be 0 or 1');
                break;
        }
    }

    /**
     * alternatives to scandir()
     * @param  string $dir 目录
     * @return array
     */
    public static function scandir($dir)
    {
        if ($dh = @opendir($dir)) {  
            while (false !== ($filename = readdir($dh))) {
                if ($filename !== '.' && $filename !== '..') {
                    $files[] = $filename;
                }
            }
            sort($files);
            return $files;
        } else {
            return array();
        }
    }

    /**
     * 构造链接
     * @param  string $path 路径
     * @return string       返回构造后的链接
     */
    static public function pathJoin($path /*[, $path2[, $path3[ ... ]]]*/)
    {
        $args = func_get_args();
        $path = array_reduce($args, function($hold, $item) {
            $_path = '';
            if (substr($item, 0, 1) === '/' || preg_match('/^[a-z]\:\/\//i', $item)) {
                $_path = $item;
            }
            else if (substr($item, 0, 2) === './') {
                $_path = $hold . substr($item, 2);
            }
            else if (substr($item, 0, 3) === '../') {
                $last_back_slash_pos = strrpos($hold, '/');
                if ($last_back_slash_pos === 0 || strpos($hold, '/') === $last_back_slash_pos - 1) {
                    return $hold;
                }
                else {
                    $_path = substr($hold, $last_back_slash_pos) . substr($item, 3);
                }
            }
            else {
                $_path = $hold . $item;
            }

            return in_array(substr($_path, -1), ['/', '\\']) ? $_path : $_path . '/';
        });

        $path = str_replace('\\', '/', $path);
        return preg_match('/[^\/]\/$/', $path) ? substr($path, 0, -1) : $path;
    }

    /**
     * 根据缓存名称和缓存根路径来获得语音数据
     * @param  string $name       缓存语音名称
     * @param  string $cache_root 缓存根目录
     * @return string             语音数据
     */
    static public function getAudioByName($name, $cache_root)
    {
        $audio_cache_dir = self::pathJoin($cache_root, $name);
        $files = self::scandir($audio_cache_dir);

        if ($files && !empty($files)) {
            $files = array_diff($files, array('.', '..'));
            /* 排序文件 */
            natsort($files);

            if (self::checkFilesVaild($files)) {
                $audio = '';
                /* 遍历文件并合成数据 */
                foreach ($files as $file) {
                    if (is_file($audio_cache_dir . '/' . $file)) {
                        $audio .= file_get_contents($audio_cache_dir . '/' . $file);
                    }
                }

                return $audio;
            }
        }

        throw new \Exception('Audio has not been found in cache.', 1);
    }

    /**
     * 获得语音数据
     * @param  string  $name
     * @return string  语音数据
     */
    public function getAudio($name)
    {
        try {
            return self::getAudioByName($name, $this->cacheRoot);
        }
        catch (Exception $e) {
            self::clearAudio(self::pathJoin($this->cacheRoot, $name));
            throw new \Exception($e->getMessage(), 1);
        }
    }

    /**
     * 输出音频内容
     * @param  string $name 缓存语音的目录名字
     * @return void
     */
    public function playAudio($name)
    {
        $data = $this->getAudio($name);
        ob_clean();
        header('Content-Type: audio/mp3');
        header('Content-Length: ' . strlen($data));
        echo $data;
    }

    /**
     * 生成语音文件
     * @param  string $name
     * @return string 语音数据
     */
    public function buildAudio($name = null)
    {
        $text_length = mb_strlen($this->tex, 'utf-8');
        $texParts = [];

        $index = 0;
        $max_index = ceil($text_length / self::TEXT_LIMIT) - 1;
        while ($index <= $max_index) {
            $texParts[] = mb_substr($this->tex, $index * self::TEXT_LIMIT, self::TEXT_LIMIT, 'utf-8');
            $text_length -= self::TEXT_LIMIT;
            $index++;
        }

        $requests = array();
        $counter = 1;
        foreach ($texParts as $tex) {
            $tex = trim(preg_replace(array('/[\x{3000}\x{00A0}]/u'), '', $tex));

            if (empty($tex)) continue;

            $post_data = array(
                'tex'   => $this->doubleUrlencode($tex),
                'lan'   => $this->lan,
                'tok'   => $this->doubleUrlencode($this->tok),
                'ctp'   => $this->ctp,
                'cuid'  => $this->doubleUrlencode($this->cuid),
                'spd'   => $this->spd,
                'pit'   => $this->pit,
                'vol'   => $this->vol,
                'per'   => $this->per,
            );

            $requests[] = array(
                'url'       => self::API_URL,
                'options'   => array(
                    CURLOPT_HEADER      => true,
                    CURLOPT_POSTFIELDS  => $this->buildPostBody($post_data),
                ),
            );
            $counter++;
        }
        // var_dump($requests);

        $Request = new Request();
        $Request->setMethod('post');
        $Request->setAsynRequests($requests);

        try {
            $Request->sendRequestAsyn();
            $responses = $Request->getAsynResponses();

            // var_dump($responses);
            // var_dump($post_data);
            // var_dump($Request->responseBody());

            if ($this->enableCache) {
                $audio_cache_dir = $this->pathJoin($this->cacheRoot, $name);
                self::clearAudio($audio_cache_dir, false);
            }

            $audio = '';
            $counter = 1;
            foreach ($responses as $response) {
                if (strpos($response->responseHeaders['content-type'], 'mp3')) {
                    $audio .= $response->responseBody;
                    if ($this->enableCache) {
                        try {
                            $this->cacheAudio($response->responseBody, $audio_cache_dir, $counter . '.mp3');
                        }
                        catch (Exception $e) {
                            $this->error = $e->getMessage();
                            return false;
                        }
                    }
                } elseif (strpos($response->responseHeaders['content-type'], 'json')) {
                    $error = json_decode($response->responseBody, true);
                    $this->error = $error['err_msg'] . '[' . $error['err_no'] . ']' . $this->buildPostBody($post_data)
                                   . '[full_tex: ' . $this->tex . ']';
                    return false;
                } else {
                    $this->error = 'Unkown error';
                    return false;
                }
                $counter++;
            }

            return $audio;
        }
        catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 清除语音文件
     * @param  array   $audio_cache_dir 语音缓存文件夹
     * @param  boolean $remove_dir      删除目录
     * @return void
     */
    public static function clearAudio($audio_cache_dir, $remove_dir = true)
    {
        if ($files = self::scandir($audio_cache_dir)) {
            foreach ($files as $file) {
                @unlink(self::pathJoin($audio_cache_dir, $file));
            }
        }

        if ($remove_dir) {
            @rmdir($audio_cache_dir);
        }
    }
}
