<?php

// Standalone smoke test script (no PHPUnit/Composer dependencies required)
// Run: php tests/smoke.php

spl_autoload_register(function ($class) {
    if (strpos($class, 'WDL\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use WDL\LayersParser;
use WDL\DataResolver;
use WDL\ElementBuilder;
use WDL\WdlDomTree;
use WDL\TokenExpander;
use WDL\RegistryCompiler;

$pass = 0;
$fail = 0;

function ok($label, $cond) {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "PASS — {$label}\n";
    } else {
        $fail++;
        echo "FAIL — {$label}\n";
    }
}

try {
    // 1) AST parsing and de-indentation
    $ast = LayersParser::parse('header>div.container>h1+p<div.banner');
    $header = $ast[0];
    $container = $header['children'][0];
    $banner = $header['children'][1];
    
    ok('< operator de-indents (container and banner are siblings under header)',
        $header['tag'] === 'header' &&
        in_array('container', $container['classes']) &&
        count($container['children']) === 2 &&
        in_array('banner', $banner['classes'])
    );

    $astMulti = LayersParser::parse('div.shell>main>article>h1<<footer');
    $shell = $astMulti[0];
    $footer = $shell['children'][1];
    ok('<< operator de-indents multiple levels (footer is sibling of main under shell)',
        count($shell['children']) === 2 &&
        $shell['children'][0]['tag'] === 'main' &&
        $footer['tag'] === 'footer'
    );

    // 2) Advanced operators (<*N, <@N)
    $astRep = LayersParser::parse('div.shell>main>article>h1<*3footer');
    $shellRep = $astRep[0];
    $footerRep = $astRep[1];
    ok('<*3 operator de-indents 3 levels (footer is sibling of shell under root)',
        count($astRep) === 2 &&
        $shellRep['tag'] === 'div' &&
        $footerRep['tag'] === 'footer'
    );

    $astDepth = LayersParser::parse('div.shell>main>article>h1<@1footer');
    $shellDepth = $astDepth[0];
    $footerDepth = $shellDepth['children'][1];
    ok('<@1 operator de-indents directly to depth 1 (footer is child of shell)',
        count($shellDepth['children']) === 2 &&
        $footerDepth['tag'] === 'footer'
    );

    // 3) Multiple class error restriction
    $threw = false;
    try {
        LayersParser::parse('div.card.featured');
    } catch (\Exception $err) {
        $threw = strpos($err->getMessage(), 'Multiple dot selectors in "div.card.featured" are not allowed') !== false;
    }
    ok('enforces 1 semantic_id per node (multiple dot selectors throw error)', $threw);

    // 4) Array Ingestion and WdlDomTree
    $tree = WdlDomTree::from([
        'form.login',
        '> div.container',
        '> h2.title',
        '+ input.email',
        '<@1 a.forgot'
    ]);
    ok('WdlDomTree parses flat array strings', $tree->getLength() === 5);
    ok('WdlDomTree toString outputs valid layers string', $tree->toString() === 'form.login > div.container > h2.title + input.email <@1 a.forgot');
    ok('WdlDomTree findIndexByClass works', $tree->findIndexByClass('title') === 2);
    
    $tuples = $tree->toTuples();
    ok('WdlDomTree normalized tuple depth matches layers specification',
        $tuples[0][0] === 0 &&
        $tuples[1][0] === 0 &&
        $tuples[2][0] === 0 &&
        $tuples[3][0] === 0 &&
        $tuples[4][0] === 0
    );

    // 5) Data Resolver Interpolation
    $res = DataResolver::resolveStr('Hello ${name}', ['name' => 'World']);
    ok('data resolver resolves path ${name} -> World', $res === 'Hello World');

    // 6) Token Expander
    $expanded = TokenExpander::expandScopedVars('p-$_{pad} bg-$_{bg}', ['pad' => '${spacing-card}', 'bg' => '#ffffff'], []);
    ok('token expander translates Scoped Vars with prefix', $expanded === 'p-[var(--spacing-card)] bg-[#ffffff]');

    // 7) Registry v2.0 compiler inheritance and variants
    $rawRegistry = [
        '__tokens__' => [
            'vars' => [
                'bg' => '#ffffff',
                'spacing-card' => '1.5rem'
            ]
        ],
        'btn' => [
            'base' => 'py-2 px-4 rounded-$_{radius}',
            'vars' => [
                'radius' => '0.375rem'
            ]
        ],
        'btn-primary' => [
            'uses' => ['btn'],
            'base' => 'bg-blue-500 text-white hover:bg-blue-600'
        ]
    ];
    $normReg = RegistryCompiler::normalizeRegistry($rawRegistry);
    $themeCss = $normReg['themeCss'];
    $btnPrimary = $normReg['normalizedRegistry']['btn-primary'];
    
    ok('RegistryCompiler compiles theme CSS', strpos($themeCss, '--bg: #ffffff;') !== false);
    ok('RegistryCompiler resolves uses inheritance', strpos($btnPrimary['class'], 'py-2 px-4 rounded-[0.375rem]') !== false && strpos($btnPrimary['class'], 'bg-blue-500') !== false);

    // 8) Pluggable hooks & loop indices
    $opts = [
        'transformText' => function($text, $node) {
            return strtoupper($text);
        }
    ];

    $nodes = LayersParser::parse('p.title*items');
    $node = $nodes[0];
    $attr = ['.title' => ['text' => '${value}']];
    $data = [
        'items' => [
            ['value' => 'first'],
            ['value' => 'second']
        ]
    ];
    
    $html = ElementBuilder::toHTML($node, $attr, $data, [], $opts);
    
    ok('transformText hook processes component text values', strpos($html, 'FIRST') !== false && strpos($html, 'SECOND') !== false);
    ok('data-wdl-index emitted correctly in array loop', strpos($html, 'data-wdl-index="0"') !== false && strpos($html, 'data-wdl-index="1"') !== false);
    ok('wdl-comp component identifier emitted', strpos($html, 'wdl-comp="title"') !== false);

} catch (\Exception $e) {
    $fail++;
    echo "FAIL — Unexpected exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n{$pass} passed, {$fail} failed\n";
exit($fail ? 1 : 0);
