<?php

/**
 * 阿里Oss
 */

namespace dux\lib\upload;

class OssDriver implements UploadInterface {

    protected $config = [
        'access_id' => '',
        'secret_key' => '',
        'bucket' => '',
        'domain' => '',
        'url' => ''

    ];
    protected $errorMsg = '';

    public function __construct($config = []) {
        $this->config = array_merge($this->config, (array)$config['driverConfig']);
        $this->config['url'] = trim($this->config['url'], '/');
    }

    public function rootPath($path) {
        if (empty($this->config['access_id']) || empty($this->config['secret_key']) || empty($this->config['bucket']) || empty($this->config['domain']) || empty($this->config['url'])) {
            $this->errorMsg = '请先配置Oss上传参数！';
            return false;
        }
        return true;
    }

    public function checkPath($path) {
        return true;
    }

    public function saveFile($fileData) {
        $name = $fileData['savename'];
        $content = fopen(realpath($fileData['tmp_name']), 'r');
        $request = \dux\lib\Http::request($this->getUrl($name, 'PUT'), 'PUT', [], [
            'body' => $content
        ]);
        if ($request === false) {
            $this->errorMsg = \dux\lib\Http::getError();
            return false;
        }
        $fileData['url'] = $this->config['domain'] . '/' . $name;
        return $fileData;
    }

    public function delFile($name) {
        $request = \dux\lib\Http::request($this->getUrl($name , 'DELETE'), 'DELETE');
        if ($request === false) {
            $this->errorMsg = \dux\lib\Http::getError();
            return false;
        }
        return true;
    }

    public function getUrl($name, $type) {
        return $this->config['url'] . '/' . trim($name, '/') . '?' . http_build_query($this->urlSignature($name, $type));
    }

    public function urlSignature($name, $type) {
        $time = time() + 1800;
        $policy = $type . "\n";
        $policy .= "\n";
        $policy .= "\n";
        $policy .= $time . "\n";
        $policy .= '/' . $this->config['bucket'] . '/' . trim($name, '/');
        $signature = base64_encode(hash_hmac('sha1', $policy, $this->config['secret_key'], true));
        return [
            'OSSAccessKeyId' => $this->config['access_id'],
            'Expires' => $time,
            'Signature' => $signature
        ];
    }

    public function getError() {
        return $this->errorMsg;
    }
}