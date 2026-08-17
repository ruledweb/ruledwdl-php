<?php

namespace WDL;

class DataResolver {
    public static function resolvePath($obj, $path) {
        $segments = explode('.', $path);
        $curr = $obj;
        
        foreach ($segments as $k) {
            if ($curr === null || $curr === '') {
                return '';
            }
            if (is_array($curr)) {
                if (array_key_exists($k, $curr)) {
                    $curr = $curr[$k];
                } else {
                    return '';
                }
            } elseif (is_object($curr)) {
                if (property_exists($curr, $k)) {
                    $curr = $curr->$k;
                } elseif (isset($curr->$k)) {
                    $curr = $curr->$k;
                } else {
                    return '';
                }
            } else {
                return '';
            }
        }
        
        return $curr ?? '';
    }

    public static function resolveStr($str, $data) {
        if (!is_string($str)) {
            return $str;
        }
        return preg_replace_callback('/\$\{([\w.]+)\}/', function($matches) use ($data) {
            $val = self::resolvePath($data, $matches[1]);
            if (is_array($val) || is_object($val)) {
                return json_encode($val);
            }
            return (string)$val;
        }, $str);
    }

    public static function resolveAll($obj, $data) {
        if ($obj === null) {
            return null;
        }
        if (is_string($obj)) {
            return self::resolveStr($obj, $data);
        }
        if (is_array($obj)) {
            $res = [];
            foreach ($obj as $k => $v) {
                $res[$k] = self::resolveAll($v, $data);
            }
            return $res;
        }
        if (is_object($obj)) {
            $res = new \stdClass();
            foreach (get_object_vars($obj) as $k => $v) {
                $res->$k = self::resolveAll($v, $data);
            }
            return $res;
        }
        return $obj;
    }
}
