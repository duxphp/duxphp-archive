<?php

namespace dux\lib;

/**
 * 翻译类
 */
class Translation {

    private $_config = [
        'app_id'        => '',
        'app_secret'    => '',
        'rand'          => ''
    ];

    private $_url = 'https://fanyi-api.baidu.com/api/trans/vip/translate';

    /**
     * 转换类型
     * @var array|string[]
     */
    private $_kind = [
        'zh-cn'     => 'zh',
        'en_us'     => 'en',
    ];

    public function __construct($config = [])
    {
        $this->_config = array_merge($this->_config,$config);
        if(isset($this->_config['kind'])){
            $this->_kind = array_merge($this->_kind,$this->_config['kind']);
        }
    }

    /**
     * 语种标识转换
     * @param $type
     * @return string
     */
    private function kind($type)
    {
        return $this->_kind[$type] ?? $type;
    }

    /**
     * @param string $q 为必填参数，值为你要翻译的内容。
     * @param string $tl 为必填参数，值为 zh-cn，表示翻译成中文
     * @param string $sl 为必填参数，值为 auto，表示自动检测语言
     * @return false|string
     */
    protected function url(string $q,string $tl,string $sl = 'zh-cn')
    {
        if($tl === $sl){
            //语言相同 不需要翻译
            return false;
        }
        return sprintf($this->_url . '?from=%s&to=%s&q=%s&appid=%s&salt=%s&sign=%s',$this->kind($sl),$this->kind($tl),$q,$this->_config['app_id'],$this->rand(true),$this->sign($q));
    }

    /**
     * 随机数
     * @param $update
     * @return string
     */
    private function rand($update = false)
    {
        if(empty($this->_config['rand']) || $update){
            $this->_config['rand'] = rand(10000,99999);
        }
        return $this->_config['rand'];
    }

    /**
     * 签名
     * @return string
     */
    private function sign(string $q)
    {
        return md5($this->_config['app_id'] . $q . $this->rand() . $this->_config['app_secret']);
    }

    /**
     * 翻译
     * @param string $q
     * @param string $tl
     * @param int $cycle
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translation(string $q,string $tl = 'en_us',int $cycle = 0)
    {
        $url = $this->url($q,$tl);
        if($url === false){
            return false;
        }
        try {
            $response = (new \GuzzleHttp\Client())->request('GET', $url);
            $reason = $response->getStatusCode();
            if ($reason <> 200) {
                throw new \Exception("Translation failed!");
            }
            $data = $response->getBody()->getContents();
            $data = json_decode($data,true);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception("Translation Error: " . $e->getMessage());
        }

        if($cycle == 0 && isset($data['error_code']) && $data['error_code'] == 54003){
            sleep(1);
            $data = $this->translation($q,$tl,++$cycle);
        }

        $data = implode('',array_column($data['trans_result'],'dst'));
        return $data;
    }

}