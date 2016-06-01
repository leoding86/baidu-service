<?php
namespace leoding86\BaiduService;

class TTS
{
    const API_URL = 'http://tsn.baidu.com/text2audio';
    const TEXT_LIMIT = 256 - 1;

    private $enableCache;
    private $cacheRoot;
    private $cacheName;
    private $audioCacheDir;

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
     * 可以此时获得访问令牌
     * @param string  $tok          访问令牌
     * @param boolean $enable_cache 是否允许缓存
     * @param string  $cache_root   缓存目录
     */
    public function __construct($tok, $enable_cache = false, $cache_root = null)
    {
        try {
            $this->setTok($tok);
            $this->setEnableCache((bool)$enable_cache);
            if ($cache_root) {
                $this->setCacheRoot($cache_root);
            }
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
    private function checkFilesVaild($files)
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
     * 清除语音文件
     * @param  array $files 语音文件集合
     * @return void
     */
    private function clearAudio($files)
    {
        foreach ($files as $file) {
            @unlink($this->pathJoin($this->audioCacheDir, $file));
        }
        /* 删除缓存目录 */
        @rmdir($this->audioCacheDir);
    }

    /**
     * 构造POST字符串
     * 
     * @param  array $params 一唯数组
     * @return string $body
     */
    private function buildPostBody($params)
    {
        $body = '';
        foreach ($params as $name => $value) {
            $body .= $name . '=' . $value . '&';
        }
        return $body;
    }

    /**
     * 保存语音文件
     * @param  string $data     语音数据
     * @param  string $filename 保存的文件名
     * @return void
     */
    private function cacheAudio($data, $filename)
    {
        $cache_file = $this->pathJoin($this->audioPath, $filname);
        touch($cache_file);
        file_put_contents($cache_file, $data);
    }

    /**
     * 两次转化链接参数
     * @param  string $string 链接参数值
     * @return string         转化以后的值
     */
    private function doubleUrlencode($string)
    {
        return urlencode(urlencode($string));
    }

    /**
     * 设置是否可以缓存语音
     * @param boolean $enable
     */
    public function setEnableCache($enable)
    {
        $this->enableCache = (bool)$enable;
    }

    public function setCacheRoot($dir)
    {
        $this->cacheRoot = $dir;
        if (!is_dir($this->cacheRoot)) {
            throw new \Exception("The directory of root of cache is not exists", 1);
        }
    }

    public function setCacheName($dir)
    {
        $this->audioCacheDir = $this->pathJoin($this->cacheRoot, $dir);
        if (!is_dir($this->audioCacheDir)) {
            throw new \Exception("Audio cache directory is not exists", 1);
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
     * @param int $per 0为女声，1为男生
     */
    public function setPer($per)
    {
        if ($per !== 1 || $per !== 0) {
            throw new \Exception('Argument \'per\' must be 0 or 1');
        }
        $this->per = $per;
    }

    /**
     * 构造链接
     * @param  string $path 路径
     * @return string       返回构造后的链接
     */
    public function pathJoin($path /*[, $path2[, $path3[ ... ]]]*/)
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
     * 获得语音数据
     * @param  string  $name
     * @return string  语音数据
     */
    public function getAudio($name)
    {
        $files = @scandir($this->audioCacheDir);

        if ($files && $files = array_diff($files, array('.', '..')) && !empty($files)) {
            /* 排序文件 */
            natsort($files);

            if ($this->checkFilesVaild($files)) {
                $audio = '';
                /* 遍历文件并合成数据 */
                foreach ($files as $file) {
                    if (is_file($this->audioCacheDir . '/' . $file)) {
                        $audio .= file_get_contents($this->audioCacheDir . '/' . $file);
                    }
                }

                return $audio;
            }
            /* 清除文件 */
            else {
                $this->clearAudio($files);
            }
        }

        throw new \Exception('Audio has not been found in cache.');
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
    public function buildAudio($name)
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
            $filename .= $counter;
            $post_data = array(
                'tex'   => $this->doubleUrlencode($tex),
                'lan'   => $this->lan,
                'ctp'   => $this->ctp,
                'cuid'  => $this->doubleUrlencode($this->cuid),
                'tok'   => $this->doubleUrlencode($this->accessToken),
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

        $Request = new Request();
        $Request->method('post');
        $Request->setAsynRequests($requests);
        $Request->sendRequestAsyn();
        $results = $Request->getAsynResponses();

        // var_dump($post_data);
        // var_dump($Request->resultBody());

        $audio = '';
        $counter = 1;
        foreach ($results as $result) {
            if ($result->resultHeaders['content-type'] == 'audio/mp3') {
                $audio .= $result->resultBody;
                if ($this->enableCache) {
                    $this->cacheAudio($result->resultBody, $this->pathJoin($name, $counter . '.mp3'));
                }
            }
            else {
                throw new \Exception('Some error occurs when build audio', 1);
            }
            $counter++;
        }

        return $audio;
    }

}