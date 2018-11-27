<?php

/**
 * 腾讯云Cos
 */

namespace dux\lib\upload;

class CosDriver implements UploadInterface {

    protected $config = [
        'SecretId' => '',
        'SecretKey' => '',
        'bucket' => '',
        'domain' => '',
        'url' => ''
    ];
    protected $errorMsg = '';

    public function __construct($config = array()) {
        $this->config = array_merge($this->config, (array)$config['driverConfig']);
    }

    public function rootPath($path) {
        if (empty($this->config['SecretId']) || empty($this->config['SecretKey']) || empty($this->config['bucket']) || empty($this->config['domain']) || empty($this->config['url'])) {
            $this->errorMsg = '请先配置Cos上传参数！';
            return false;
        }
        return true;
    }

    public function checkPath($path) {
        return true;
    }

    public function saveFile($fileData) {

        $date = gmdate('Y-m-d\TH:i:s\Z', strtotime('+1 day'));
        $name = $fileData['savename'];
        $policy = [
            'expiration' => $date,
            'conditions' => [
                ['content-length-range',0,104857600],
                ['bucket' => trim($this->config['bucket'])]
            ]
        ];
        $policy = base64_encode(stripslashes(json_encode($policy)));
        $_header = [
            'Authorization' => $this->sign($name),
            'Content-Length' =>$fileData['size'],
        ];

        $postFields = array(
            'key' => $name,
            'policy' =>$policy,
            'Signature' =>$this->sign(),
            'file' => curl_file_create(realpath($fileData['tmp_name']), $fileData['type'], $name),
        );
        $data = $this->curl($this->config['url'], $postFields, $_header );

        if (!empty($data)) {
            $data = simplexml_load_string($data);
            $data = json_decode(json_encode($data), TRUE);

        }
        if (!empty($data['Message'])) {
            $this->errorMsg = $data['Message'];
            return false;
        }
        $fileData['url'] = $this->config['domain'] . '/' . $name;
        return $fileData;
    }

    public function curl($url, $post_data, $header = '') {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($header));
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getError() {
        return $this->errorMsg;
    }

    /**
     * 获取Cos验证签名
     * @param array $keys
     * @param string $method
     * @param string $pathname
     * @param array $query
     * @param array $headers
     * @return string
     */
    private function sign($keys=[], $method='post', $pathname='', $query = array(), $headers = array()){

        $SecretId = isset($keys['SecretId'])?$keys['SecretId']:trim($this->config['SecretId']);
        $SecretKey = isset($keys['SecretKey'])?$keys['SecretKey']:trim($this->config['SecretKey']);
        $query = array();
        $headers = array();
        $method = strtolower($method ?: 'post');
        $pathname = $pathname ? : '/';
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);
        $now = time() - 1;
        $expired = $now + 86400;
        $qSignAlgorithm = 'sha1';
        $qAk = $SecretId;
        $qSignTime = $now . ';' . $expired;
        $qKeyTime = $now . ';' . $expired;
        $qHeaderList = strtolower(implode(';', $this->getObjectKeys($headers)));
        $qUrlParamList = strtolower(implode(';', $this->getObjectKeys($query)));
        $signKey = hash_hmac("sha1", $qKeyTime, $SecretKey);
        $formatString = implode("\n", array(strtolower($method), $pathname, $this->obj2str($query), $this->obj2str($headers), ''));
        $stringToSign = implode("\n", array('sha1', $qSignTime, sha1($formatString), ''));
        $qSignature = hash_hmac('sha1', $stringToSign, $signKey);
        $authorization = implode('&', array(
            'q-sign-algorithm=' . $qSignAlgorithm,
            'q-ak=' . $qAk,
            'q-sign-time=' . $qSignTime,
            'q-key-time=' . $qKeyTime,
            'q-header-list=' . $qHeaderList,
            'q-url-param-list=' . $qUrlParamList,
            'q-signature=' . $qSignature
        ));
        return $authorization;
    }

    private function getObjectKeys($obj)
    {
        $list = array_keys($obj);
        sort($list);
        return $list;
    }
    private function obj2str($obj)
    {
        $list = array();
        $keyList = $this->getObjectKeys($obj);
        $len = count($keyList);
        for ($i = 0; $i < $len; $i++) {
            $key = $keyList[$i];
            $val = isset($obj[$key]) ? $obj[$key] : '';
            $key = strtolower($key);
            $list[] = rawurlencode($key) . '=' . rawurlencode($val);
        }
        return implode('&', $list);
    }
}