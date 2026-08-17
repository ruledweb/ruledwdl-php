<?php

namespace WDL;

class LayersParser {
    public static function parse($raw) {
        if ($raw instanceof WdlDomTree) {
            return self::parse($raw->toString());
        }
        if (is_array($raw)) {
            return self::parse(WdlDomTree::from($raw)->toString());
        }

        $str = preg_replace('/[()]/', '', (string)$raw);

        // Check for unsupported characters: {, }, [, ], ^
        if (preg_match('/[{}\[\]^]/', $str, $matches)) {
            $ch = $matches[0];
            $hint = '';
            if ($ch === '{' || $ch === '}') {
                $hint = 'Inline text `{...}` is not supported. Reference DATA with ${key} or set text via attr object.';
            } elseif ($ch === '[' || $ch === ']') {
                $hint = 'Inline attributes `[name=value]` are not supported. Put attributes in attr object.';
            } else {
                $hint = 'Climb-up `^` is not supported. Use `<` for de-indentation / subset instead.';
            }
            throw new \Exception("parseLayers: unsupported character \"{$ch}\" in \"{$str}\". {$hint}");
        }

        $root = [
            'tag' => '__root__',
            'classes' => [],
            'children' => []
        ];

        $stack = [&$root];
        $i = 0;
        $len = strlen($str);

        // helper to get reference of last item in stack
        $topIndex = function() use (&$stack) {
            return count($stack) - 1;
        };

        $parseElementToken = function() use (&$str, &$i, $len) {
            $isComponent = false;
            if ($i < $len && $str[$i] === '@') {
                $isComponent = true;
                $i++;
            }

            $tag = '';
            while ($i < $len && preg_match('/[a-zA-Z0-9_-]/', $str[$i])) {
                $tag .= $str[$i];
                $i++;
            }

            $id = null;
            if (preg_match('/#([\w-]+)/', $tag, $idM)) {
                $id = $idM[1];
            }

            $classes = [];
            while ($i < $len && $str[$i] === '.') {
                if (count($classes) >= 1) {
                    $errToken = ($isComponent ? '@' : '') . $tag . '.' . $classes[0] . '.';
                    $i++;
                    while ($i < $len && preg_match('/[a-zA-Z0-9_-]/', $str[$i])) {
                        $errToken .= $str[$i];
                        $i++;
                    }
                    throw new \Exception(
                        "parseLayers: Multiple dot selectors in \"{$errToken}\" are not allowed. " .
                        "WDL Layers strictly enforce one semantic_id per element (e.g. \"tag.semantic_id\"). " .
                        "For additional CSS classes, use REGISTRY tokens or the attr object (e.g. attr['.{$classes[0]}'].class)."
                    );
                }
                $i++;
                $cls = '';
                while ($i < $len && preg_match('/[a-zA-Z0-9_-]/', $str[$i])) {
                    $cls .= $str[$i];
                    $i++;
                }
                if ($cls !== '') {
                    $classes[] = $cls;
                }
            }

            $repeat = 1;
            $loopKey = null;
            if ($i < $len && $str[$i] === '*') {
                $i++;
                $mult = '';
                while ($i < $len && preg_match('/[a-zA-Z0-9_.]/', $str[$i])) {
                    $mult .= $str[$i];
                    $i++;
                }
                if (preg_match('/^\d+$/', $mult)) {
                    $repeat = (int)$mult;
                } elseif ($mult !== '') {
                    $loopKey = $mult;
                }
            }

            $n = [
                'tag' => ($isComponent ? '@' : '') . ($tag ?: 'div'),
                'classes' => $classes,
                'children' => []
            ];

            if ($id !== null) {
                $n['id'] = $id;
            }
            if ($loopKey !== null) {
                $n['loopKey'] = $loopKey;
            }
            if ($repeat > 1) {
                $n['repeat'] = $repeat;
            }

            return $n;
        };

        while ($i < $len) {
            $ch = $str[$i];

            if ($ch === '>') {
                $parentIndex = $topIndex();
                $parent =& $stack[$parentIndex];
                $lastIndex = count($parent['children']) - 1;
                if ($lastIndex >= 0) {
                    $stack[] =& $parent['children'][$lastIndex];
                }
                $i++;
            } elseif ($ch === '+') {
                $i++;
            } elseif ($ch === '<') {
                $i++;
                if ($i < $len && $str[$i] === '*') {
                    $i++;
                    $numStr = '';
                    while ($i < $len && preg_match('/\d/', $str[$i])) {
                        $numStr .= $str[$i];
                        $i++;
                    }
                    $count = ($numStr !== '') ? intval($numStr) : 1;
                    for ($k = 0; $k < $count; $k++) {
                        if (count($stack) > 1) {
                            array_pop($stack);
                        }
                    }
                } elseif ($i < $len && $str[$i] === '@') {
                    $i++;
                    $numStr = '';
                    while ($i < $len && preg_match('/\d/', $str[$i])) {
                        $numStr .= $str[$i];
                        $i++;
                    }
                    $targetDepth = ($numStr !== '') ? intval($numStr) : 0;
                    $targetStackLen = $targetDepth + 1;
                    while (count($stack) > $targetStackLen && count($stack) > 1) {
                        array_pop($stack);
                    }
                } else {
                    $count = 1;
                    while ($i < $len && $str[$i] === '<') {
                        $count++;
                        $i++;
                    }
                    for ($k = 0; $k < $count; $k++) {
                        if (count($stack) > 1) {
                            array_pop($stack);
                        }
                    }
                }
            } elseif (preg_match('/\s/', $ch)) {
                $i++;
            } else {
                $el = $parseElementToken();
                $parentIndex = $topIndex();
                $parent =& $stack[$parentIndex];
                $parent['children'][] = $el;
            }
        }

        return $root['children'];
    }

    public static function matchAttr($node, $attr) {
        if (!is_array($attr)) return [];
        foreach ($node['classes'] ?? [] as $c) {
            if (isset($attr['.' . $c])) {
                return $attr['.' . $c];
            }
        }
        return $attr[$node['tag'] ?? ''] ?? [];
    }
}
