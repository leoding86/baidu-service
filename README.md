# baidu-service
一些百度服务工具库

##说明：

----------

###ICacheObject 接口

**__construct($save_dir, $save_name)**
构造实例并初始化配置
$save_dir  string 缓存目录
$save_name string 缓存文件名

**cacheAccessToken($access_token)**
缓存令牌信息
$access_token string 需要缓存的令牌信息

**getAccessToken()**
获得令牌信息

----------

###ClientCredentialsOauth 类

#####公共方法

**__construct($cache_object, $client_id, $client_secret, $grant_type)**  
构造实例并初始化配置，如果初始化出错，将会抛出 \Exception 异常  
$cache_object  缓存操作对象 
$client_id     应用的app id  
$client_secret 应用的app secret  
$grant_type    授权类型，暂时仅支持 client_credentials

**public function getRawAccessToken()**  
获得原始的令牌信息，JSON格式  
return string

**public function getFormatAccessToken()**  
获得格式化的令牌信息  
return array

**public function readAccessToken()**  
请求令牌信息，如果出错会抛出一个 \Exception 异常  

----------

###Request 类
#####处理一些网络请求的帮助类

#####公共方法
**public function __construct($url = null, $method = null, $params = null, $need_encoding = false)**  
构造请求实例，并可以初始化单个网络请求，构造出错抛出 \Exception 异常  
$url           string 请求链接  
$method        string 请求类型  
$params        array  请求参数  
$need_encoding boolean 是否对$params进行url encode

**public function setUrl($url)**  
设置请求链接  
$url string 请求链接

**public function setParams(array $params)**  
设置请求的参数  
$params array 请求参数数组

**public function setMethod($method = 'get')**  
设置请求的类型  
$get string 请求类型，例如post，get等  

**public function needEncoding($needEncoding)**  
设置是否需要对请求参数url encode  
$needEncoding boolean true为编码，false为不编码  

**public function getResponse()**  
获得响应数据  
return string  

**public function getResponseBody()**  
获得响应正文  
return string  

**public function getResponseHeaders()**  
获得响应头  
return array  

**public function setAsynRequests(array $asyn_requests)**  
设置异步请求集，每个请求的链接以及额外的curl设置  
$asyn_requests array [['url' => array, 'options' => array], ...]  

public function getAsynResponses  
获得异步响应集  
return array  

**public function sendRequest($options = array())**  
发送请求，可以额外设置curl设置  
$options array curl参数数组  

**public function sendRequestAsyn()**  
发送异步请求  

--------
###TTS 类
#####Test to Speech 服务

#####公共方法
**public function __construct($tok, $cache_root = null)**  
构造方法，做部分初始化工作  
$tok        string 百度Rest API的访问令牌  
$cache_root string 缓存语音文件的根路径  

**public function enableCache($enable)**  
设置是否可以缓存语音  
$enable boolean true为缓存，false为不缓存  

**public function setCacheRoot($dir)**  
设置语音缓存根目录  
$dir string 有效目录  

**public function setTex($tex)**  
设置需要合成的文本  
$tex string 文本内容  

**public function setLan($lan = 'zh')**  
设置合成语音的语言  
$lan string 语言代码，暂只支持'zh'  

**public function setTok($tok)**  
设置访问令牌  
$tok string 访问令牌  

**public function setCtp($ctp = 1)**  
设置客户端类型  
$ctp int 客户端类型代码，暂只支持1  

**public function setCuid($cuid)**  
设置用户唯一标识  
$cuid string 用户标识  

**public function setSpd($spd = 5)**  
设置语速  
$spd int 语速范围0-9  

**public function setPit($pit = 5)**  
设置语调  
$pit int 语调范围0-9  

**public function setVol($vol = 5)**  
设置音量  
$vol int 音量范围0-9  

**public function setPer($per = 0)**  
设置朗读性别  
$per int 0为女声，1为男声  

**static public function pathJoin($path [, $path2[, $path3[ ... ]]])**  
粘连目录路径，类似nodejs的path.join  
$path  string 路径  
$path2 string 路径2  
...  

**static public function getAudioByName($name, $cache_root)**  
根据缓存名称和缓存根路径来获得语音数据  
$name       string 缓存语音名称  
$cache_root string 缓存根目录  
return      string 语音数据

**public function getAudio($name)**  
根据缓存名称来获得语音数据  
$name  string 缓存语音名称  
return string 语音数据  

**public function playAudio($name)**  
根据缓存名称来输出可播放数据  
$name string 缓存语音名称


**public function buildAudio($name)**  
生成语音文件  
$name string 缓存目录名称  