<?php
/**
 *	@author 349703434@qq.com
*/

//php7.1以下需要
//composer require spomky-labs/php-aes-gcm
//类库地址:https://github.com/Spomky-Labs/php-aes-gcm
require_once __DIR__ . '/vendor/autoload.php';

use AESGCM\AESGCM;

class Antiaddiction
{
    private $appId = NULL;
    
    private $secretKey = NULL;
    
    private $bizId = NULL;
    
    private $api_url = 'http://api2.wlc.nppa.gov.cn';
	
	protected $curl = NULL;
    
    public function __construct()
    {
		$this->curl = new Curl();
    }
    
    /**
     * 实名认证查询
     * @param string $platform
     * @param string $username
     */
    public function authentication_check($platform , $username , $name , $idNum)
    {
        $url_path = '/idcard/authentication/check';
        $ai = $this->getAi($platform, $username);
        $param = [
            'ai'=>$ai,
            'name'=>$name,
            'idNum'=>$idNum
        ];
        $json = $this->api_post($url_path, $param);
        print_r($json);
    }
    
    
    /**
     * 实名认证结果查询
     * @param string $platform
     * @param string $username
     * /idcard/authentication/query
     */
    public function authentication_query( $platform , $username )
    {
        $url_path = '/idcard/authentication/query';
        $ai = $this->getAi($platform, $username);
        $param = [
            'ai'=>$ai,
        ];
        $json = $this->api_get($url_path, $param);
        print_r($json);
    }
    
    /**
     * 实名认证结果查询
     * @param string $platform
     * @param string $username
     * /behavior/collection/loginout
     */
    public function collection_loginout( $platform , $username )
    {
        $url_path = '/behavior/collection/loginout';
        $ai = $this->getAi($platform, $username);
        $param = [
            'collections'=>[
                [
                    'no'=>'int(3)在批量模式中标识一条行为数据，取值范围 1-128',
                    'si'=>'string(32)游戏内部会话标识',//一个会话标识只能对应唯一的实名用户，一个实名用户可以拥有多个会话标识；同一用户单次游戏会话中，上下线动作必须使用同一会话标识上报备注：会话标识仅标识一次用户会话，生命周期仅为一次上线和与之匹配的一次下线，不会对生命周期之外的任何业务有任何影响
                    'bt'=>'int(1)用户行为类型',//0：下线1：上线
                    'ot'=>'long(10)行为发生时间', //'行为发生时间戳，单位秒',
                    'ct'=>'Int(1)上报类型',//用户行为数据上报类型0：已认证通过用户2：游客用户
                    'di'=>'String(32)设备标识',//游客模式设备标识，由游戏运营单位生成，游客用户下必填
                    'pi'=>'string(38)用户唯一标识',//已通过实名认证用户的唯一标识，已认证通过用户必填
                ]
            ],
        ];
        $json = $this->api_post($url_path, $param);
        print_r($json);
    }
    
    
    public function api_post($url_path , $data , $timeout = 10 , array $opts = [])
    {
        $url = $this->requestUrl($url_path);
        $rawBody = $this->createBody($data);
        $sign = $this->createSign($rawBody);
        $headers = $this->getHeaders($sign);
        $options = [CURLOPT_TIMEOUT=>$timeout] + $opts;
        foreach ($options as $code=>$value)
        {
            $this->curl->option($code , $value);
        }
        foreach ($headers as $code=>$value)
        {
            $this->curl->http_header($code , $value);
        }
        $ret = $this->curl->simple_post($url , $rawBody);
        $json = json_decode($ret,TRUE);
        return $json;
    }
    
    public function api_get($url_path , array $data = [] , $timeout = 10 , array $opts = [])
    {
        $url = $this->requestUrl($url_path);
        $sign = $this->createSign($data);
        $headers = $this->getHeaders($sign);
        $options = [CURLOPT_TIMEOUT=>$timeout] + $opts;
        foreach ($options as $code=>$value)
        {
            $this->curl->option($code , $value);
        }
        foreach ($headers as $code=>$value)
        {
            $this->curl->http_header($code , $value);
        }
        $ret = $this->curl->simple_get($url , $data);
        $json = json_decode($ret,TRUE);
        return $json;
    }
    
    public function appid( $appId )
    {
        $account_list = [
            '3ab38ff***********************733'=>[
                'appId'=>'3ab38ff***********************733',
                'secretKey'=>'709b9****************e5c5c3',
                'bizId'=>'1234566634',
            ],
        ];
        if(isset($account_list[$appId]))
        {
            foreach ($account_list[$appId] as $k=>$v)
            {
                $this->$k = trim($v);
            }
        }
        else
        {
            foreach (['appId','secretKey','bizId'] as $k)
            {
                $this->$k = NULL;
            }
        }
        return $this;
    }
    
    
    public function getAi($platform , $username)
    {
        return md5($platform . "|" . $username);
    }
    
    public function getHeaders( $sign = NULL )
    {
        return [
            'Content-Type'=>"application/json;charset=utf-8",
            'appId'=>$this->appId,
            'bizId'=>$this->bizId,
            'timestamps'=>$this->milistime(),
            'sign'=>$sign,
        ];
    }
    
    public function createSign($rawBody)
    {
        $data = $this->getHeaders();
        if(is_array($rawBody)) $data += $rawBody;
        ksort($data);
        $source = [$this->secretKey];
        foreach ($data as $key=>$value)
        {
            if($key !== 'sign' && $key != 'Content-Type')
            {
                $source[] = "{$key}{$value}";
            }
        }
        if( ! is_array($rawBody) ) $source[] = $rawBody;
        $presign = implode("", $source);
        return hash("sha256", $presign);
    }
    
    public function createBody($string)
    {
        return json_encode(['data'=>$this->aesGcmEncrypt($string) ]);
    }
    
    protected function requestUrl($url_path)
    {
        $url_path = ltrim($url_path , "/");
        return "{$this->api_url}/{$url_path}";
    }
    
    /**
     * 单次程序执行返回同一个时间戳
     * @return NULL|string
     */
    public function milistime()
    {
        static $milistime = NULL ;
        if(is_null($milistime))
        {
            $timestr = explode(' ', microtime());
            $milistime = strval(sprintf('%d%03d',$timestr[1],$timestr[0] * 1000));
        }
        return $milistime;
    }
    
    public function aesGcmDecrypt($content)
    {
        $ciphertextwithiv = bin2hex(base64_decode($content));
        $iv = substr($ciphertextwithiv, 0,24);
        $tag = substr($ciphertextwithiv , -32, 32);
        $ciphertext = substr($ciphertextwithiv, 24 , strlen($ciphertextwithiv)-24-32);
        require APPPATH . 'vendor/autoload.php';
        return AESGCM::decrypt(hex2bin($this->secretKey), hex2bin($iv), hex2bin($ciphertext), NULL, hex2bin($tag));
        //如果环境是php7.1+,直接使用下面的方式
//         return openssl_decrypt(hex2bin($ciphertext), $cipher, hex2bin($secretKey), OPENSSL_RAW_DATA, hex2bin($iv),hex2bin($tag));
    }
    
    public function aesGcmEncrypt($string)
    {
        $cipher = strtolower('AES-128-GCM');
        if(is_array($string)) $string = json_encode($string);
        //二进制key
        $skey = hex2bin($this->secretKey);
        //二进制iv
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        require APPPATH . 'vendor/autoload.php';
        list($content, $tag) = AESGCM::encrypt($skey, $iv, $string);
        //如果环境是php7.1+,直接使用下面的方式
//         $tag = NULL;
//         $content = openssl_encrypt($string, $cipher, $skey,OPENSSL_RAW_DATA,$iv,$tag);
        $str = bin2hex($iv) .  bin2hex($content) . bin2hex($tag);
        return base64_encode(hex2bin($str));
    }
}