<?php

namespace dux\lib;

/**
 * 翻译类
 */
class Translation {

//https://clients5.google.com/translate_a似乎不再返回忠实的翻译；只返回翻译后的文本。
//https://translate.googleapis.com/translate_a/single仍然返回忠实的翻译和一些关于翻译的信息（源语言、翻译信心和音译，如果有的话）。
//https://translate.google.com/_/TranslateWebserverUi/data/batchexecute只返回一个忠实的翻译X-Goog-BatchExecute-Bgr，创建这个标题的算法似乎还没有被破译。这是返回最多信息（源语言、翻译置信度、音译、替代翻译、定义、示例等）的端点。
//https://translate.google.com/m返回忠实的翻译，但没有额外的信息。返回 HTML 而不是 JSON，因此需要解析。

//    private $_url = 'https://translate.google.com/translate_a/t';
    private $_url = 'https://clients5.google.com/translate_a/t';

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

        return sprintf($this->_url . '?client=%s&dt=t&sl=%s&tl=%s&q=%s','gtx',$sl,$tl,$q);
    }

    /**
     * 翻译
     * @param string $q
     * @param string $tl
     * @return false|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function translation(string $q,string $tl = 'en_US')
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

        return $data;
    }

}