<?php

namespace WDL;

class ElementBuilder {
    public static function esc($str) {
        return str_replace(
            ['&', '<', '>', '"'],
            ['&amp;', '&lt;', '&gt;', '&quot;'],
            strval($str)
        );
    }

    public static function buildEl($node, $attr, $data, $registry, $opts = []) {
        $base = [];
        $globalTokens = $registry['__tokens__']['vars'] ?? [];

        foreach ($node['classes'] ?? [] as $c) {
            if (isset($registry[$c])) {
                $norm = RegistryCompiler::normalizeRegistryEntry($registry[$c], $globalTokens);
                $resolved = DataResolver::resolveAll($norm, $data);
                if (is_array($resolved)) {
                    $base = array_merge($base, $resolved);
                }
            }
        }

        $matchedAttr = LayersParser::matchAttr($node, $attr);
        $matched = DataResolver::resolveAll($matchedAttr, $data);
        if (!is_array($matched)) {
            $matched = [];
        }

        $res = array_merge($base, $matched);

        $refKey = isset($res['attr-ref']) ? DataResolver::resolveStr($res['attr-ref'], $data) : '';
        if ($refKey !== '' && isset($registry[$refKey])) {
            $normRef = RegistryCompiler::normalizeRegistryEntry($registry[$refKey], $globalTokens);
            $ref = DataResolver::resolveAll($normRef, $data);
            if (is_array($ref)) {
                $res = array_merge($ref, $matched);
            }
        }

        $SKIP = ['alpine' => true, 'htmx' => true, 'attr-ref' => true, 'text' => true, 'class' => true];

        $alpine = isset($res['alpine']) && is_array($res['alpine']) ? $res['alpine'] : [];
        $htmx = isset($res['htmx']) && is_array($res['htmx']) ? $res['htmx'] : [];

        $flat = array_merge($res, $alpine, $htmx);

        if (empty($flat['wdl-comp'])) {
            $flat['wdl-comp'] = (!empty($node['classes'])) ? $node['classes'][0] : $node['tag'];
        }

        if ($data !== null && isset($data['_index']) && empty($flat['data-wdl-index'])) {
            $flat['data-wdl-index'] = strval($data['_index']);
        }

        $baseClass = $base['class'] ?? '';
        $flatClass = $flat['class'] ?? '';

        $allCls = array_merge(
            explode(' ', $baseClass),
            explode(' ', $flatClass),
            $node['classes'] ?? []
        );

        $allCls = array_filter(array_map('trim', $allCls));
        $uniq = array_values(array_unique($allCls));

        $a = '';
        if (!empty($uniq)) {
            $a .= ' class="' . implode(' ', $uniq) . '"';
        }
        if (!empty($node['id'])) {
            $a .= ' id="' . self::esc($node['id']) . '"';
        }

        foreach ($flat as $k => $v) {
            if (isset($SKIP[$k]) || $v === null || $v === '') {
                continue;
            }
            $a .= ' ' . $k . '="' . self::esc($v) . '"';
        }

        $VOID = [
            'img' => true, 'br' => true, 'hr' => true, 'input' => true, 'link' => true,
            'meta' => true, 'area' => true, 'base' => true, 'col' => true, 'embed' => true,
            'param' => true, 'source' => true, 'track' => true, 'wbr' => true
        ];

        $tag = $node['tag'] ?? 'div';
        // Remove '@' component prefix if present
        if (strpos($tag, '@') === 0) {
            $tag = substr($tag, 1);
        }

        if (isset($VOID[$tag])) {
            return '<' . $tag . $a . '>';
        }

        $RAW_TEXT = ['script' => true, 'style' => true];
        $txt = '';
        if (isset($res['text']) && $res['text'] !== '') {
            if (isset($RAW_TEXT[$tag])) {
                $txt = strval($res['text']);
            } elseif (isset($opts['transformText']) && is_callable($opts['transformText'])) {
                $txt = call_user_func($opts['transformText'], strval($res['text']), $node);
            } else {
                $txt = Markdown::renderInlineMarkdown($res['text']);
            }
        }

        $ch = '';
        foreach ($node['children'] ?? [] as $child) {
            $ch .= self::toHTML($child, $attr, $data, $registry, $opts);
        }

        return '<' . $tag . $a . '>' . ($ch !== '' ? $ch : $txt) . '</' . $tag . '>';
    }

    public static function toHTML($node, $attr, $data, $registry, $opts = []) {
        if (isset($node['loopKey']) && $node['loopKey'] !== null) {
            $items = DataResolver::resolvePath($data, $node['loopKey']);
            if (is_array($items) && count($items) > 0) {
                $html = '';
                $clonedNode = $node;
                unset($clonedNode['loopKey']);

                foreach ($items as $idx => $item) {
                    $itemArray = is_object($item) ? get_object_vars($item) : (is_array($item) ? $item : []);
                    $sd = array_merge($data, $itemArray, ['_index' => $idx]);
                    $html .= self::buildEl($clonedNode, $attr, $sd, $registry, $opts);
                }
                return $html;
            }
            return '';
        }
        return self::buildEl($node, $attr, $data, $registry, $opts);
    }
}
