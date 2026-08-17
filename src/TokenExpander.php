<?php

namespace WDL;

class TokenExpander {
    public static function resolveVariableValue($varName, $localVars = [], $globalVars = []) {
        if (!is_array($localVars)) $localVars = [];
        if (!is_array($globalVars)) $globalVars = [];

        // Try local value first
        if (array_key_exists($varName, $localVars)) {
            $val = $localVars[$varName];
            // If local var references a global user token ${user-token}
            if (is_string($val) && strpos($val, '${') === 0 && substr($val, -1) === '}') {
                $userTokenKey = trim(substr($val, 2, -1));
                return "var(--{$userTokenKey})";
            }
            return strval($val);
        }

        // Try global value next
        if (array_key_exists($varName, $globalVars)) {
            return "var(--{$varName})";
        }

        return null;
    }

    public static function expandScopedVars($classString, $localVars = [], $globalVars = []) {
        if (!$classString || !is_string($classString)) return '';
        if (strpos($classString, '$_{') === false) return $classString;

        // Regex matches: (optional prefix ending with -)$_{varName}
        // Group 1: prefix
        // Group 2: varName
        $regex = '/([a-zA-Z0-9_@:-]+-)?\$_{([a-zA-Z0-9_-]+)}/';

        return preg_replace_callback($regex, function($matches) use ($localVars, $globalVars) {
            $prefix = $matches[1] ?? '';
            $varName = $matches[2];

            $resolved = self::resolveVariableValue($varName, $localVars, $globalVars);
            if ($resolved === null) {
                return $matches[0]; // Leave untouched if unresolvable
            }

            if ($prefix !== '') {
                return "{$prefix}[{$resolved}]";
            }
            return $resolved;
        }, $classString);
    }
}
