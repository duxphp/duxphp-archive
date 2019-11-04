<?php

namespace dux\com;
/**
 * 路由器
 * Class Rotue
 * @package dux\com
 * @source https://github.com/nikic/FastRoute
 */
class Rotue {

    private $uris = [];
    private $routes = [];
    private $method = ['ALL', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'];
    private $regex = "\{\s*([a-zA-Z_][a-zA-Z0-9_-]*)\s*(?::\s*([^{}]*(?:\{(?-1)\}[^{}]*)*))?\}";

    private $config = [];
    protected $object = null;


    /**
     * 添加规则
     * @param string $method
     * @param string $url
     * @param string $module
     * @throws \dux\exception\Exception
     */
    public function add(string $method, string $roule, string $module) {
        $method = strtoupper($method);
        $module = trim($module, '/');
        $url = rtrim(rtrim($roule, '/'), ']');
        $cacheKey = 'rule.' . md5($method . $module . $url);
        $cacheData = $this->cache()->get($cacheKey);
        if ($cacheData) {
            foreach ($cacheData as $vo) {
                $this->routes[$vo['method']][$vo['module']][] = [$vo['params'], $vo['url']];
                $this->uris[$vo['method']][] = [$vo['params'], $vo['url'], $vo['regex'], $vo['module']];
            }
            return true;
        }

        $matchData = array_filter(preg_split("/" . $this->regex . "(*SKIP)(*F)|\[/x", $url));
        $routeData = [];
        $tmpRoute = '';
        foreach ($matchData as $key => $vo) {
            $tmpRoute .= $vo;
            $routeData[] = $this->parseRegex($tmpRoute);
        }
        $cacheData = [];
        foreach ($routeData as $data) {
            $tmpRegex = [];
            $tmpUrl = [];
            $tmpKey = [];
            foreach ($data as $k => $v) {
                if (is_array($v)) {
                    $tmpKey[] = $v[0];
                    $tmpRegex[] = "({$v[1]})";
                    $tmpUrl[] = "{{$v[0]}}";
                } else {
                    $tmpRegex[] = $v;
                    $tmpUrl[] = $v;
                }
            }
            $regex = implode('', $tmpRegex);
            $url = implode('', $tmpUrl);
            //生成模块规则
            $this->routes[$method][$module][] = [$tmpKey, $url];
            //生成Url规则
            $this->uris[$method][] = [$tmpKey, $url, $regex, $module];
            $cacheData[] = ['method' => $method, 'params' => $tmpKey, 'url' => $url, 'regex' => $regex, 'module' => $module];
        }
        $this->cache()->set($cacheKey, $cacheData);
        return true;
    }

    /**
     * 路由调度
     * @param string $method
     * @param string $uri
     * @param callable $callback
     */
    public function dispatch(string $method, string $uri) {
        $method = strtoupper($method);
        $cacheKey = 'url.' . md5($method . $uri);
        $cacheData = $this->cache()->get($cacheKey);
        if ($cacheData) {
            return $cacheData;
        }
        $path = rtrim(str_replace('\\', '/', parse_url($uri, PHP_URL_PATH)), '/');
        $uriData = array_filter(array_merge((array)$this->uris[$method], (array)$this->uris['ALL']));
        $getUrl = array_key_first($_GET);
        if (strpos($getUrl, '/') === 0) {
            unset($_GET[$getUrl]);
        }
        if (empty($uriData)) {
            return $this->parsingModule($path, $cacheKey);
        }
        $tmpData = [];
        $key = 1;
        foreach ($uriData as $vo) {
            $tmpData[$key] = $vo;
            $num = is_array($vo[0]) ? count($vo[0]) : 0;
            $key += $num;
        }
        $regexData = array_column($uriData, 2);
        $regex = implode(' | ', $regexData);
        if (!preg_match('#^(?:' . implode('|', $regexData) . ')$#x', $path, $matches)) {
            return $this->parsingModule($path, $cacheKey);
        };
        for ($i = 1; '' === $matches[$i]; ++$i) ;
        list($keys, $url, $regex, $module) = $tmpData[$i];
        foreach ($keys as $k) {
            $_GET[$k] = $matches[$i++];
        }
        return $this->parsingModule($module, $cacheKey, $role = false);
    }

    /**
     * 解析模块
     * @param string $url
     * @param string $cacheKey
     * @param bool $role
     * @return array
     * @throws \Exception
     */
    private function parsingModule(string $url, string $cacheKey, bool $role = true) {
        $urlData = ['default_layer' => '', 'role' => '', 'layer' => '', 'app' => '', 'module' => '', 'action' => ''];
        $urlArray = explode("/", trim($url, '/'), 4);
        $moduleConfig = \dux\Config::get('dux.module');
        $moduleRule = array_flip($moduleConfig);
        $urlArray[0] = $role ? $urlArray[0] : $moduleConfig[$urlArray[0]];

        if (in_array($urlArray[0], $moduleConfig)) {
            $urlData['role'] = $urlArray[0];
            $urlData['layer'] = $moduleRule[$urlData['role']];
            $urlData['app'] = $urlArray[1];
            $urlData['module'] = $urlArray[2];
            $urlData['action'] = $urlArray[3];
        } else {
            $urlData['app'] = $urlArray[0];
            $urlData['module'] = $urlArray[1];
            $urlData['action'] = $urlArray[2];
        }
        $urlData['default_layer'] = \dux\Config::get('dux.module_default');
        $urlData['layer'] = $urlData['layer'] ?: \dux\Config::get('dux.module_default');
        $urlData['app'] = strtolower($urlData['app'] ?: 'index');
        $urlData['module'] = ucfirst($urlData['module'] ?: 'Index');
        $urlData['action'] = $urlData['action'] ?: 'index';
        if (IS_CLI) {
            $urlData['default_layer'] = 'cli';
            $urlData['role'] = 'x';
            $urlData['layer'] = 'cli';
        }
        $this->cache()->set($cacheKey, $urlData);
        return $urlData;
    }

    /**
     * 生成Url
     * @param string $method
     * @param string $module
     * @param array $params
     * @return mixed|string
     */
    public function get(string $method, string $module, array $params = []) {
        $method = strtoupper($method);
        if ($method == 'ALL') {
            $routes = [];
            foreach ($this->routes as $vo) {
                $routes = array_merge($routes, $vo);
            }
        } else {
            $routes = $this->routes[$method];
        }
        $routes = $routes[$module];
        if (empty($routes)) {
            return $this->parseUrl($module, $params);
        }
        $paramKeys = array_keys($params);
        $tmp = [];
        foreach ($routes as $key => $vo) {
            $tmp[$key] = count(array_diff($paramKeys, $vo[0]));
        }
        $key = array_search(min($tmp), $tmp);
        list($tmpRoute, $url) = $routes[$key];

        $routeParams = [];
        foreach ($tmpRoute as $vo) {
            if (!isset($params[$vo])) {
                return $this->parseUrl($module, $params);
            }
            $routeParams[$vo] = $params[$vo];
        }
        $otherParams = array_diff($params, $routeParams);
        $otherParams = http_build_query($otherParams);
        foreach ($routeParams as $key => $vo) {
            $url = str_replace("{{$key}}", $vo, $url);
        }
        return $otherParams ? $url . '?' . $otherParams : $url;
    }

    /**
     * 获取方法
     * @return array
     */
    public function method() {
        return $this->method;
    }

    /**
     * 解析Url
     * @param string $str
     * @return string
     */
    private function parseUrl(string $str, array $params) {
        $str = trim($str);
        $str = trim($str, '/');
        $str = trim($str, '\\');
        $param = explode('/', $str, 4);
        $param = array_filter($param);
        $paramCount = count($param);
        $module = \dux\Config::get('dux.module');
        switch ($paramCount) {
            case 1:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = MODULE_NAME;
                $action = lcfirst($param[0]);
                break;
            case 2:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = ucfirst($param[0]);
                $action = lcfirst($param[1]);
                break;
            case 3:
                $layer = LAYER_NAME;
                $app = strtolower($param[0]);
                $controller = ucfirst($param[1]);
                $action = lcfirst($param[2]);
                break;
            case 4:
                $layer = $param[0] == 'default' ? DEFAULT_LAYER_NAME : $param[0];
                $app = strtolower($param[1]);
                $controller = ucfirst($param[2]);
                $action = lcfirst($param[3]);
                break;
            case 0:
            default:
                $layer = LAYER_NAME;
                $app = APP_NAME;
                $controller = MODULE_NAME;
                $action = ACTION_NAME;
                break;
        }
        $longUrl = $module[$layer] . '/' . $app . '/' . $controller . '/' . $action;
        if ($layer <> DEFAULT_LAYER_NAME) {
            $url = $longUrl;
        } else {
            $url = $app . '/' . $controller . '/' . $action;
        }
        return '/' . $url . ($params ? '?' . http_build_query($params) : '');
    }

    /**
     * 解析规则
     * @param string $route
     * @return array
     */
    private function parseRegex(string $route) {
        if (!preg_match_all('/' . $this->regex . '/x', $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [$route];
        }
        $offset = 0;
        $routeData = [];
        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }
            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : '[^/]+'
            ];
            $offset = $set[0][1] + strlen($set[0][0]);
        }
        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }
        return $routeData;
    }


    /**
     * 获取缓存对象
     * @return \dux\com\Cache
     * @throws \Exception
     */
    public function cache() {
        if ($this->object) {
            return $this->object;
        }
        $this->object = \dux\Dux::cache();
        return $this->object;
    }
}