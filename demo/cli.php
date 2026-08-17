<?php

// Standalone CLI demo runner for WDL PHP layout engine
// Run: php demo/cli.php

// 1. Setup autoloader for WDL namespace
spl_autoload_register(function ($class) {
    if (strpos($class, 'WDL\\') === 0) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use WDL\LayoutComposer;
use WDL\MemoryStore;

echo "===========================================\n";
echo "       WDL PHP Layout Engine CLI Demo      \n";
echo "===========================================\n\n";

// 2. Initialize MemoryStore with a mock layout
$store = new MemoryStore([
    'layouts' => [
        'base-layout' => [
            'name' => 'base-layout',
            'COMPONENTS' => [
                [
                    'layers' => 'div.wrapper>main.main-content',
                    'attr' => [
                        '.wrapper' => ['class' => 'min-h-screen bg-slate-900 text-slate-100'],
                        '.main-content' => ['class' => 'container mx-auto px-4 py-12', 'text' => '{{content}}']
                    ]
                ]
            ]
        ]
    ]
]);

// 3. Define the complete WDL page as a single unified configuration
$wdlPagePayload = [
    'title' => 'WDL CLI Demo Page',
    'layout' => 'base-layout',
    'REGISTRY' => [
        'hdr' => ['class' => 'border-b border-slate-800 py-4 mb-12'],
        'container' => ['class' => 'container mx-auto px-4 flex justify-between items-center'],
        'logo' => ['class' => 'text-xl font-bold'],
        'navigation' => ['class' => 'flex gap-4'],
        'nav-link' => ['class' => 'text-slate-400 hover:text-white transition-colors'],
        'hero' => ['class' => 'text-center max-w-2xl mx-auto mb-12'],
        'hero-title' => ['class' => 'text-4xl font-extrabold tracking-tight mb-4'],
        'hero-desc' => ['class' => 'text-lg text-slate-400 mb-6'],
        'btn-group' => ['class' => 'flex justify-center gap-4'],
        'btn-primary' => ['class' => 'px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-lg transition-colors'],
        'btn-secondary' => ['class' => 'px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium rounded-lg transition-colors'],
        'grid-container' => ['class' => 'grid md:grid-cols-2 gap-8'],
        'card' => ['class' => 'p-6 bg-slate-800/50 border border-slate-700/50 rounded-xl'],
        'card-title' => ['class' => 'text-xl font-bold mb-2'],
        'card-desc' => ['class' => 'text-slate-400'],
        'ftr' => ['class' => 'border-t border-slate-800 py-6 mt-12'],
        'ftr-container' => ['class' => 'container mx-auto px-4 text-center text-sm text-slate-500']
    ],
    'COMPONENTS' => [
        // Navigation header
        [
            'layers' => 'header.hdr>div.container>span.logo+nav.navigation>a.nav-link*nav_items',
            'attr' => [
                '.logo' => ['text' => 'WDL Demo Suite'],
                '.nav-link' => ['text' => '${label}', 'href' => '${url}']
            ]
        ],
        // Hero Section
        [
            'layers' => 'section.hero>h1.hero-title+p.hero-desc+div.btn-group>a.btn-primary+a.btn-secondary',
            'attr' => [
                '.hero-title' => ['text' => 'WDL PHP Core Compiler'],
                '.hero-desc' => ['text' => 'A clean, lightweight, and robust port of the Web Definition Language layout engine to server-side PHP.'],
                '.btn-primary' => ['text' => 'Get Started', 'href' => '#'],
                '.btn-secondary' => ['text' => 'GitHub Repo', 'href' => '#']
            ]
        ],
        // Features Grid
        [
            'layers' => 'div.grid-container>div.card*features>h2.card-title+p.card-desc',
            'attr' => [
                '.card-title' => ['text' => '${title}'],
                '.card-desc' => ['text' => '${desc}']
            ]
        ],
        // Footer
        [
            'layers' => 'footer.ftr>div.ftr-container',
            'attr' => [
                '.ftr-container' => ['text' => '© ' . date('Y') . ' WDL PHP. All rights reserved.']
            ]
        ]
    ],
    'DATA' => [
        'nav_items' => [
            ['label' => 'Home', 'url' => '#'],
            ['label' => 'Documentation', 'url' => '#']
        ],
        'features' => [
            ['title' => 'Layout Composition', 'desc' => 'Inherit nested templates and layouts dynamically on the server-side.'],
            ['title' => 'Token Cascading', 'desc' => 'Seamlessly pass down brand, theme, and design tokens to components.']
        ],
        '__design_tokens' => ':root { --primary-color: #6366f1; --primary-hover: #4f46e5; }',
        '__brand_tokens' => ':root { --brand-font: "Inter", sans-serif; }',
        '__seo' => [
            'description' => 'Demo showing the Web Definition Language core PHP implementation compiling layout markup programmatically.',
            'og_title' => 'WDL PHP CLI Demo',
            'og_type' => 'website'
        ]
    ]
];

try {
    echo "Compiling layout using LayoutComposer...\n";
    $result = LayoutComposer::composePage($store, 'cli-project', $wdlPagePayload);
    $html = $result['html'];
    
    $outputPath = __DIR__ . '/output.html';
    echo "Saving compiled HTML to: " . $outputPath . "\n";
    file_put_contents($outputPath, $html);
    
    echo "\nSuccess! Compiled output size: " . strlen($html) . " bytes.\n";
    echo "You can open '" . realpath($outputPath) . "' in your browser to view the layout.\n";
    echo "===========================================\n";
} catch (\Exception $e) {
    echo "Error during compilation: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
