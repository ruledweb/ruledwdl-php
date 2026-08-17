<?php

namespace WDL;

class RegistryCompiler {
    public static function compileGlobalTokens($tokensVars = []) {
        if (!$tokensVars || !is_array($tokensVars)) return '';
        $rules = [];
        foreach ($tokensVars as $key => $val) {
            if ($val !== null && $val !== '') {
                $rules[] = "  --{$key}: {$val};";
            }
        }
        if (empty($rules)) return '';
        return ":root {\n" . implode("\n", $rules) . "\n}";
    }

    public static function resolveTokenInheritance($key, $registry = [], &$visited = []) {
        $raw = $registry[$key] ?? null;
        if ($raw === null || !is_array($raw)) return $raw ?: [];
        if (isset($visited[$key])) return $raw; // Prevent cyclic inheritance
        $visited[$key] = true;

        $uses = $raw['uses'] ?? null;
        if (!is_array($uses) || empty($uses)) {
            unset($visited[$key]);
            return $raw;
        }

        $merged = [
            'vars' => [],
            'base' => '',
            'variants' => [],
            'states' => [],
            'breakpoints' => [],
            'containers' => [],
            'scopes' => []
        ];

        foreach ($uses as $parentKey) {
            $parentResolved = self::resolveTokenInheritance($parentKey, $registry, $visited);
            if ($parentResolved && is_array($parentResolved)) {
                $merged['vars'] = array_merge($merged['vars'], $parentResolved['vars'] ?? []);
                $merged['base'] = implode(' ', array_filter([$merged['base'], $parentResolved['base'] ?? '']));
                $merged['variants'] = array_merge($merged['variants'], $parentResolved['variants'] ?? []);
                $merged['states'] = array_merge($merged['states'], $parentResolved['states'] ?? []);
                $merged['breakpoints'] = array_merge($merged['breakpoints'], $parentResolved['breakpoints'] ?? []);
                $merged['containers'] = array_merge($merged['containers'], $parentResolved['containers'] ?? []);
                $merged['scopes'] = array_merge($merged['scopes'], $parentResolved['scopes'] ?? []);
            }
        }

        // Child overrides parent
        $result = array_merge($merged, $raw);
        $result['vars'] = array_merge($merged['vars'], $raw['vars'] ?? []);
        $result['base'] = implode(' ', array_filter([$merged['base'], $raw['base'] ?? '']));
        $result['variants'] = array_merge($merged['variants'], $raw['variants'] ?? []);
        $result['states'] = array_merge($merged['states'], $raw['states'] ?? []);
        $result['breakpoints'] = array_merge($merged['breakpoints'], $raw['breakpoints'] ?? []);
        $result['containers'] = array_merge($merged['containers'], $raw['containers'] ?? []);
        $result['scopes'] = array_merge($merged['scopes'], $raw['scopes'] ?? []);

        unset($visited[$key]);
        return $result;
    }

    public static function normalizeRegistryEntry($entry, $globalTokens = []) {
        if (!$entry) return [];

        if (is_string($entry)) {
            return ['class' => $entry];
        }

        if (!is_array($entry)) {
            return [];
        }

        $isV2 = (
            array_key_exists('base', $entry) ||
            array_key_exists('variants', $entry) ||
            array_key_exists('states', $entry) ||
            array_key_exists('breakpoints', $entry) ||
            array_key_exists('containers', $entry) ||
            array_key_exists('scopes', $entry) ||
            array_key_exists('uses', $entry) ||
            array_key_exists('vars', $entry)
        );

        if (!$isV2) {
            return $entry;
        }

        $localVars = $entry['vars'] ?? [];
        $classes = [];

        // Base utilities
        if (!empty($entry['base'])) {
            $classes[] = TokenExpander::expandScopedVars($entry['base'], $localVars, $globalTokens);
        }

        // Default variant
        if (!empty($entry['variants']) && is_array($entry['variants'])) {
            $variantName = $entry['defaultVariant'] ?? null;
            if ($variantName === null && !empty($entry['variants'])) {
                $keys = array_keys($entry['variants']);
                $variantName = $keys[0];
            }
            if ($variantName !== null && isset($entry['variants'][$variantName])) {
                $classes[] = TokenExpander::expandScopedVars($entry['variants'][$variantName], $localVars, $globalTokens);
            }
        }

        // States
        if (!empty($entry['states']) && is_array($entry['states'])) {
            foreach ($entry['states'] as $state => $cls) {
                if ($cls) {
                    $expanded = TokenExpander::expandScopedVars($cls, $localVars, $globalTokens);
                    $formatted = implode(' ', array_map(function($c) use ($state) {
                        return (strpos($c, ':') !== false) ? $c : "{$state}:{$c}";
                    }, explode(' ', $expanded)));
                    $classes[] = $formatted;
                }
            }
        }

        // Breakpoints
        if (!empty($entry['breakpoints']) && is_array($entry['breakpoints'])) {
            foreach ($entry['breakpoints'] as $bp => $cls) {
                if ($cls) {
                    $expanded = TokenExpander::expandScopedVars($cls, $localVars, $globalTokens);
                    $formatted = implode(' ', array_map(function($c) use ($bp) {
                        return (strpos($c, ':') !== false) ? $c : "{$bp}:{$c}";
                    }, explode(' ', $expanded)));
                    $classes[] = $formatted;
                }
            }
        }

        // Containers
        if (!empty($entry['containers']) && is_array($entry['containers'])) {
            foreach ($entry['containers'] as $cont => $cls) {
                if ($cls) {
                    $expanded = TokenExpander::expandScopedVars($cls, $localVars, $globalTokens);
                    $formatted = implode(' ', array_map(function($c) use ($cont) {
                        return (strpos($c, ':') !== false) ? $c : "{$cont}:{$c}";
                    }, explode(' ', $expanded)));
                    $classes[] = $formatted;
                }
            }
        }

        $finalClass = implode(' ', array_filter($classes));

        // Preserve non-V2 extra attributes
        $SKIP_V2_KEYS = ['base', 'variants', 'defaultVariant', 'states', 'breakpoints', 'containers', 'scopes', 'uses', 'vars'];
        $extraAttrs = [];
        foreach ($entry as $k => $v) {
            if (!in_array($k, $SKIP_V2_KEYS, true)) {
                $extraAttrs[$k] = $v;
            }
        }

        return array_merge(['class' => $finalClass], $extraAttrs);
    }

    public static function normalizeRegistry($rawRegistry = []) {
        if (!$rawRegistry || !is_array($rawRegistry)) {
            return ['normalizedRegistry' => [], 'themeCss' => ''];
        }

        $globalTokens = $rawRegistry['__tokens__']['vars'] ?? [];
        $themeCss = self::compileGlobalTokens($globalTokens);

        $normalizedRegistry = [];

        foreach ($rawRegistry as $key => $_entry) {
            if ($key === '__tokens__' || $key === '$version') continue;
            $visitedContext = [];
            $resolvedEntry = self::resolveTokenInheritance($key, $rawRegistry, $visitedContext);
            $normalizedRegistry[$key] = self::normalizeRegistryEntry($resolvedEntry, $globalTokens);
        }

        return [
            'normalizedRegistry' => $normalizedRegistry,
            'themeCss' => $themeCss
        ];
    }
}
