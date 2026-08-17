<?php

namespace WDL;

class WdlDomTree {
    private static $ALLOWED_OPS = ['', '>', '+', '<'];

    public static function validateOperator($op) {
        if ($op === null || $op === '') return '';
        $str = trim((string)$op);
        if (in_array($str, self::$ALLOWED_OPS, true)) return $str;
        if (preg_match('/^<\*\d+$/', $str)) return $str; // <*N repeater
        if (preg_match('/^<@\d+$/', $str)) return $str; // <@N depth reference
        throw new \Exception("WDLDomTree: Invalid operator \"{$op}\". Allowed operators are: \"\", \">\", \"+\", \"<\", \"<*N\", \"<@N\".");
    }

    public static function validateSemanticId($sem) {
        if (!$sem) return '';
        $str = trim(ltrim((string)$sem, '.'));
        if (strpos($str, '.') !== false) {
            throw new \Exception("WDLDomTree: Multiple dot selectors in \"{$sem}\" are not allowed. WDL Layers strictly enforce one semantic_id per node (no .class1.class2).");
        }
        return $str;
    }

    public static function normalizeTuple($entry) {
        if (is_array($entry)) {
            $depth = 0;
            $op = '';
            $tag = 'div';
            $sem = '';
            $rep = null;

            if (isset($entry[0]) && is_numeric($entry[0])) {
                // 5-element tuple: [depth, operator, tag, semantic_id, repeator]
                $depth = max(0, intval($entry[0]));
                $op = self::validateOperator($entry[1] ?? '');
                $tag = strtolower(strval($entry[2] ?? 'div'));
                $sem = self::validateSemanticId($entry[3] ?? '');
                $rep = !empty($entry[4]) ? (string)$entry[4] : null;
            } else {
                // 4-element tuple: [operator, tag, semantic_id, repeator]
                $op = self::validateOperator($entry[0] ?? '');
                $tag = strtolower(strval($entry[1] ?? 'div'));
                $sem = self::validateSemanticId($entry[2] ?? '');
                $rep = !empty($entry[3]) ? (string)$entry[3] : null;
            }
            return [$depth, $op, $tag, $sem, $rep];
        }

        if (is_object($entry)) {
            $entryArr = get_object_vars($entry);
            $depth = max(0, intval($entryArr['depth'] ?? 0));
            $op = self::validateOperator($entryArr['op'] ?? ($entryArr['operator'] ?? ''));
            $tag = strtolower(strval($entryArr['tag'] ?? ($entryArr['node'] ?? 'div')));
            $sem = self::validateSemanticId($entryArr['semantic_id'] ?? ($entryArr['class'] ?? ''));
            $rep = $entryArr['repeator'] ?? ($entryArr['loopKey'] ?? null);
            return [$depth, $op, $tag, $sem, $rep];
        }

        if (is_string($entry)) {
            return self::parseStringTokenToTuple($entry);
        }

        return [0, '', 'div', '', null];
    }

    public static function parseStringTokenToTuple($tokenStr) {
        if (!$tokenStr || !is_string($tokenStr)) return [0, '', 'div', '', null];

        $str = trim($tokenStr);
        $op = '';

        // Extract operator at beginning
        if (strpos($str, '<*') === 0) {
            if (preg_match('/^<\*\d+/', $str, $matches)) {
                $op = $matches[0];
                $str = trim(substr($str, strlen($op)));
            }
        } elseif (strpos($str, '<@') === 0) {
            if (preg_match('/^<@\d+/', $str, $matches)) {
                $op = $matches[0];
                $str = trim(substr($str, strlen($op)));
            }
        } elseif (strpos($str, '>') === 0) {
            $op = '>';
            $str = trim(substr($str, 1));
        } elseif (strpos($str, '+') === 0) {
            $op = '+';
            $str = trim(substr($str, 1));
        } elseif (strpos($str, '<') === 0) {
            $count = 0;
            while (strpos($str, '<') === 0) {
                $count++;
                $str = substr($str, 1);
            }
            $op = $count > 1 ? "<*{$count}" : '<';
            $str = trim($str);
        }

        self::validateOperator($op);

        // Extract repeator multiplier / loop key (*3 or *posts)
        $rep = null;
        $multIdx = strpos($str, '*');
        if ($multIdx !== false) {
            $rep = substr($str, $multIdx + 1);
            $str = substr($str, 0, $multIdx);
        }

        // Parse tag and class
        $tag = 'div';
        $sem = '';

        if (strpos($str, '.') !== false) {
            $parts = explode('.', $str);
            if (count($parts) > 2) {
                throw new \Exception("WDLDomTree: Multiple dot selectors in \"{$str}\" are not allowed. WDL Layers strictly enforce one semantic_id per node (no .class1.class2).");
            }
            $tag = $parts[0] ?: 'div';
            $sem = self::validateSemanticId($parts[1]);
        } else {
            $tag = $str ?: 'div';
        }

        return [0, $op, strtolower($tag), $sem, $rep];
    }

    public $state = [];

    public function __construct($input = null) {
        if ($input !== null) {
            $this->load($input);
        }
    }

    public static function from($input) {
        return new self($input);
    }

    public function load($input) {
        if ($input === null) {
            $this->state = [];
            return $this;
        }

        if ($input instanceof WdlDomTree) {
            $this->state = $input->toTuples();
            return $this;
        }

        if (is_array($input)) {
            $this->state = array_map([self::class, 'normalizeTuple'], $input);
            return $this;
        }

        if (is_string($input)) {
            $tokens = array_filter(preg_split('/\s+/', trim($input)));
            $this->state = array_map([self::class, 'parseStringTokenToTuple'], $tokens);
            return $this;
        }

        return $this;
    }

    public function getLength() {
        return count($this->state);
    }

    public function getAt($index) {
        if ($index < 0 || $index >= count($this->state)) return null;
        return $this->state[$index];
    }

    public function findIndexByClass($semanticId) {
        $target = self::validateSemanticId($semanticId);
        foreach ($this->state as $idx => $tuple) {
            if ($tuple[3] === $target) {
                return $idx;
            }
        }
        return -1;
    }

    public function findIndexByTag($tag) {
        $target = strtolower((string)$tag);
        foreach ($this->state as $idx => $tuple) {
            if ($tuple[2] === $target) {
                return $idx;
            }
        }
        return -1;
    }

    public function append($depth, $operator, $tag, $semanticId, $repeator = null) {
        $tuple = [
            max(0, intval($depth)),
            self::validateOperator($operator),
            strtolower(strval($tag ?: 'div')),
            self::validateSemanticId($semanticId),
            $repeator ? (string)$repeator : null
        ];
        $this->state[] = $tuple;
        return $this;
    }

    public function insertAt($index, $entry) {
        $tuple = self::normalizeTuple($entry);
        $safeIdx = max(0, min($index, count($this->state)));
        array_splice($this->state, $safeIdx, 0, [$tuple]);
        return $this;
    }

    public function removeAt($index) {
        if ($index >= 0 && $index < count($this->state)) {
            array_splice($this->state, $index, 1);
        }
        return $this;
    }

    public function reorder($fromIndex, $toIndex) {
        if ($fromIndex >= 0 && $fromIndex < count($this->state)) {
            $moved = array_splice($this->state, $fromIndex, 1);
            $safeTarget = max(0, min($toIndex, count($this->state)));
            array_splice($this->state, $safeTarget, 0, $moved);
        }
        return $this;
    }

    public function wrap($targetIndex, $wrapperTag, $wrapperSemanticId) {
        if ($targetIndex < 0 || $targetIndex >= count($this->state)) return $this;
        $target = $this->state[$targetIndex];
        $parentDepth = $target[0];

        $wrapper = [$parentDepth, $target[1] ?: '+', strtolower(strval($wrapperTag ?: 'div')), self::validateSemanticId($wrapperSemanticId), null];
        array_splice($this->state, $targetIndex, 0, [$wrapper]);

        // Indent target node as child of wrapper
        $this->state[$targetIndex + 1][0] = $parentDepth + 1;
        $this->state[$targetIndex + 1][1] = '>';
        return $this;
    }

    public function toTuples() {
        return array_map(function($t) { return $t; }, $this->state);
    }

    public function toString() {
        return implode(' ', array_map(function($tuple) {
            $op = $tuple[1] ? "{$tuple[1]} " : '';
            $tag = $tuple[2] ?: 'div';
            $sem = $tuple[3] ? ".{$tuple[3]}" : '';
            $rep = $tuple[4] ? (strpos($tuple[4], '*') === 0 ? $tuple[4] : "*{$tuple[4]}") : '';
            return "{$op}{$tag}{$sem}{$rep}";
        }, $this->state));
    }
}
