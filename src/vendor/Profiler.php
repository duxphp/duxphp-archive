<?php


namespace dux\vendor;
/**
 * Class Profiler
 * @package Acf
 * @author clover <cloverphp@qq.com>
 */
class Profiler {
    protected static $initTime = 0;
    protected static $queryLog = [];
    protected static $debugLog = [];
    protected static $traceLog = [];

    /**
     * Profiler constructor.
     */
    public function __construct() {
        self::$initTime = round(microtime(true), 2);
    }

    /**
     * @param bool $mili
     * @return float
     */
    public static function elasped($mili = true) {
        if ($mili) {
            return round((round(microtime(true), 2) - self::$initTime) * 1000, 2);
        } else {
            return round(round(microtime(true), 2) - self::$initTime, 2);
        }
    }

    public static function trace() {
        foreach (func_get_args() as $msg) {
            self::$traceLog[] = $msg;
        }
    }

    public static function debug() {
        foreach (func_get_args() as $msg) {
            self::$debugLog[] = $msg;
        }
    }

    /**
     * @return array
     */
    public static function fetch() {
        $profiler = [
            'memusage' => self::fileSize2Unit(\memory_get_usage()),
            'cpuusage' => \file_exists('/proc/loadavg') ? substr(\file_get_contents('/proc/loadavg'), 0, 4) : false,
            'timeusage' => self::elasped(),
            "path" => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : [],
            'header' => self::getRequestHeader(),
            'params' => file_get_contents("php://input"),
            'trace' => self::$traceLog,
            'debug' => self::$debugLog,
            'query' => self::$queryLog,
        ];
        return $profiler;
    }

    /**
     * @param $queryStr
     * @param $sTime
     * @param $category
     */
    public static function saveQuery($queryStr, $sTime, $category) {
        $nowMiliSec = self::elasped();
        $miliSecond = round(($nowMiliSec + $sTime), 2);
        $elapsed = round($nowMiliSec, 2);
        $start = round($nowMiliSec - $miliSecond, 2);
        self::$queryLog[] = str_pad("({$miliSecond}ms On {$start}-{$elapsed}ms)", 15, " ", STR_PAD_RIGHT) . str_pad($category, 5, " ", STR_PAD_LEFT) . ":$queryStr";
        return;
    }

    /**
     * @param $bytes
     * @return string
     */
    private static function fileSize2Unit($bytes) {
        if ($bytes >= 1099511627776)
            return number_format($bytes / 1073741824, 2) . ' TB';
        elseif ($bytes >= 1073741824)
            return number_format($bytes / 1073741824, 2) . ' GB';
        elseif ($bytes >= 1048576)
            return number_format($bytes / 1048576, 2) . ' MB';
        elseif ($bytes >= 1024)
            return number_format($bytes / 1024, 2) . ' KB';
        elseif ($bytes > 1)
            return $bytes . ' bytes';
        elseif ($bytes == 1)
            return $bytes . ' byte';
        else return '0 bytes';
    }

    /**
     * @return array
     */
    private static function getRequestHeader() {
        $header = array();
        foreach ($_SERVER as $Key => $Value) {
            if (substr($Key, 0, 5) === 'HTTP_') {
                $header[strtolower(str_replace("HTTP_", "", $Key))] = $Value;
            }
        }
        return $header;
    }
}