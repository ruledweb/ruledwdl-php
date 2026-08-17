<?php
// Setup autoloading
spl_autoload_register(function ($class) {
    if (strpos($class, 'WDL\\') === 0) {
        $file = __DIR__ . '/../../src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use WDL\LayoutComposer;
use WDL\MemoryStore;

// Define default WDL Page JSON presets
$presets = [
    'hero' => [
        'title' => 'Hero Banner',
        'payload' => [
            'title' => 'Hero Banner Demo',
            'REGISTRY' => [
                'hero-sec' => ['class' => 'text-center py-24 px-6 bg-slate-900 text-white'],
                'hero-container' => ['class' => 'max-w-3xl mx-auto flex flex-col items-center'],
                'badge' => ['class' => 'bg-indigo-600/10 text-indigo-300 border border-indigo-500/30 px-3 py-1 rounded-full text-xs font-semibold mb-4 display-inline-block'],
                'hero-title' => ['class' => 'text-5xl font-extrabold mb-6 tracking-tight leading-none'],
                'hero-desc' => ['class' => 'text-lg text-slate-400 mb-10 leading-relaxed'],
                'btn-group' => ['class' => 'flex justify-center gap-4'],
                'btn-primary' => ['class' => 'px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-lg transition-all text-decoration-none'],
                'btn-secondary' => ['class' => 'px-6 py-3 bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium rounded-lg transition-all text-decoration-none']
            ],
            'COMPONENTS' => [
                [
                    'layers' => 'section.hero-sec>div.hero-container>span.badge+h1.hero-title+p.hero-desc+div.btn-group>a.btn-primary+a.btn-secondary',
                    'attr' => [
                        '.badge' => ['text' => 'WDL Core v1.0'],
                        '.hero-title' => ['text' => 'De-clutter your Layouts with WDL'],
                        '.hero-desc' => ['text' => 'Write layouts in clean, readable Emmet-like strings. Compile them programmatically to responsive Tailwind HTML code with custom styling tokens.'],
                        '.btn-primary' => ['text' => 'Quick Start', 'href' => '#'],
                        '.btn-secondary' => ['text' => 'Read Docs', 'href' => '#']
                    ]
                ]
            ],
            'DATA' => [
                '__design_tokens' => ':root { --theme-accent: #6366f1; }'
            ]
        ]
    ],
    'cards' => [
        'title' => 'Features Grid',
        'payload' => [
            'title' => 'Features Grid Demo',
            'REGISTRY' => [
                'features-sec' => ['class' => 'py-20 px-6 bg-slate-950'],
                'features-container' => ['class' => 'max-w-6xl mx-auto'],
                'features-header' => ['class' => 'text-center mb-16'],
                'features-title' => ['class' => 'text-3xl font-extrabold text-white mb-4'],
                'features-desc' => ['class' => 'text-slate-400 max-w-xl mx-auto'],
                'grid-container' => ['class' => 'grid md:grid-cols-3 gap-8'],
                'card' => ['class' => 'bg-slate-900 border border-slate-800 p-8 rounded-xl hover:border-slate-700 transition-all duration-300'],
                'icon-box' => ['class' => 'w-12 h-12 bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-xl rounded-lg border border-emerald-500/20 mb-6'],
                'card-title' => ['class' => 'text-xl font-bold text-white mb-2'],
                'card-desc' => ['class' => 'text-slate-400 text-sm leading-relaxed']
            ],
            'COMPONENTS' => [
                [
                    'layers' => 'section.features-sec>div.features-container>div.features-header>h2.features-title+p.features-desc<div.grid-container>div.card*features>div.icon-box+h3.card-title+p.card-desc',
                    'attr' => [
                        '.features-title' => ['text' => 'WDL Architecture Features'],
                        '.features-desc' => ['text' => 'Engineered to combine the speed of server-side composition with rich frontend styles.'],
                        '.icon-box' => ['text' => '${icon}'],
                        '.card-title' => ['text' => '${title}'],
                        '.card-desc' => ['text' => '${desc}']
                    ]
                ]
            ],
            'DATA' => [
                'features' => [
                    ['icon' => '⚡', 'title' => 'Instant Compile', 'desc' => 'Compiles WDL strings dynamically to semantic HTML tags without build steps.'],
                    ['icon' => '🎨', 'title' => 'Design System', 'desc' => 'Design and brand style tokens cascade and bundle automatically.'],
                    ['icon' => '🧩', 'title' => 'Composable', 'desc' => 'Use structured layout layers and component stores to build modular apps.']
                ]
            ]
        ]
    ],
    'feedback' => [
        'title' => 'Interactive Form',
        'payload' => [
            'title' => 'Feedback Form Demo',
            'REGISTRY' => [
                'form-sec' => ['class' => 'py-20 px-6 bg-slate-900'],
                'form-card' => ['class' => 'max-w-md mx-auto bg-slate-950 p-8 border border-slate-800 rounded-2xl shadow-2xl'],
                'form-title' => ['class' => 'text-2xl font-bold text-white mb-2'],
                'form-desc' => ['class' => 'text-sm text-slate-400 mb-6'],
                'form-group' => ['class' => 'space-y-4'],
                'form-lbl' => ['class' => 'block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1'],
                'form-ctrl' => ['class' => 'w-full py-2 px-3 bg-slate-900 border border-slate-800 rounded-lg text-white text-sm outline-none focus:border-purple-500 transition-colors'],
                'btn-submit' => ['class' => 'w-full py-3 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-lg transition-colors cursor-pointer']
            ],
            'COMPONENTS' => [
                [
                    'layers' => 'section.form-sec>div.form-card>h2.form-title+p.form-desc+form.form-group>div.field-name>label.lbl-name+input.input-name<div.field-email>label.lbl-email+input.input-email<div.field-msg>label.lbl-msg+textarea.input-msg<button.btn-submit',
                    'attr' => [
                        '.form-title' => ['text' => 'Share Feedback'],
                        '.form-desc' => ['text' => 'Configure layouts dynamically using components.'],
                        '.lbl-name' => ['class' => 'form-lbl', 'text' => 'Full Name'],
                        '.input-name' => ['class' => 'form-ctrl', 'placeholder' => 'Jane Doe', 'type' => 'text'],
                        '.lbl-email' => ['class' => 'form-lbl', 'text' => 'Email Address'],
                        '.input-email' => ['class' => 'form-ctrl', 'placeholder' => 'jane@example.com', 'type' => 'email'],
                        '.lbl-msg' => ['class' => 'form-lbl', 'text' => 'Message Body'],
                        '.input-msg' => ['class' => 'form-ctrl', 'placeholder' => 'Your feedback...', 'rows' => '4'],
                        '.btn-submit' => ['text' => 'Submit Form', 'type' => 'submit']
                    ]
                ]
            ],
            'DATA' => [
                '__design_tokens' => ':root { --theme-accent: #a855f7; }'
            ]
        ]
    ]
];

// If it's a preview request, handle compile & print HTML
if (isset($_GET['preview'])) {
    $wdlPayloadRaw = $_POST['wdl_payload'] ?? '{}';
    $payload = json_decode($wdlPayloadRaw, true);

    if (!is_array($payload)) {
        echo '<!DOCTYPE html><html><head><title>JSON Decode Error</title>';
        echo '<style>body { background-color: #0f172a; color: #ef4444; font-family: system-ui, sans-serif; padding: 2rem; } pre { background-color: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; }</style>';
        echo '</head><body>';
        echo '<h1>JSON Decode Error</h1>';
        echo '<p>Unable to parse the provided payload. Ensure it is valid JSON.</p>';
        echo '</body></html>';
        exit;
    }

    $store = new MemoryStore([]);

    try {
        $result = LayoutComposer::composePage($store, 'preview-project', $payload);
        echo $result['html'];
    } catch (\Exception $e) {
        // Output clean error page in iframe
        echo '<!DOCTYPE html><html><head><title>Compilation Error</title>';
        echo '<style>body { background-color: #0f172a; color: #ef4444; font-family: system-ui, sans-serif; padding: 2rem; } pre { background-color: #1e293b; color: #e2e8f0; padding: 1rem; border-radius: 8px; overflow: auto; white-space: pre-wrap; word-break: break-all; }</style>';
        echo '</head><body>';
        echo '<h1>Compilation Error</h1>';
        echo '<p>WDL layout compiler encountered an error:</p>';
        echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
        echo '</body></html>';
    }
    exit;
}

// Format default active preset to pretty JSON string
$activePresetName = $_GET['preset'] ?? 'hero';
if (!isset($presets[$activePresetName])) {
    $activePresetName = 'hero';
}
$activePresetJSON = json_encode($presets[$activePresetName]['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WDL PHP Layout Playground</title>
    <link rel="stylesheet" href="style.css">
    <!-- Load Outfit font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo-group">
            <div class="logo-icon">W</div>
            <div class="logo-text">ruledwdl-php</div>
            <div class="tagline">Interactive Playground</div>
        </div>
        <div class="action-bar">
            <button type="button" class="btn btn-primary" onclick="compileAndPreview()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                Compile & Preview
            </button>
        </div>
    </header>

    <div class="workspace">
        <!-- Editor Panel -->
        <div class="editor-panel">
            <div class="panel-header">
                <span class="panel-title">payload.wdl.json</span>
                <span id="status-badge" class="status-badge status-success">Valid JSON</span>
            </div>

            <!-- Presets Section -->
            <div class="presets-section">
                <span class="presets-label">Presets</span>
                <div class="presets-container">
                    <?php foreach ($presets as $key => $preset): ?>
                        <div class="preset-chip<?php echo $key === $activePresetName ? ' active' : ''; ?>" 
                             onclick="loadPreset('<?php echo $key; ?>')"
                             id="preset-<?php echo $key; ?>">
                            <?php echo htmlspecialchars($preset['title']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <form id="editor-form" action="index.php?preview=1" method="POST" target="preview-iframe" class="editor-form">
                <div class="editor-container">
                    <textarea id="wdl_payload" name="wdl_payload" placeholder="{}" spellcheck="false"><?php echo htmlspecialchars($activePresetJSON); ?></textarea>
                </div>
            </form>
        </div>

        <!-- Preview Panel -->
        <div class="preview-panel">
            <div class="panel-header">
                <span class="panel-title">Live Preview</span>
            </div>
            <div class="iframe-container">
                <iframe id="preview-iframe" name="preview-iframe"></iframe>
            </div>
        </div>
    </div>

    <script>
        // Preset payload definitions
        const presetPayloads = {
            <?php foreach ($presets as $key => $preset): ?>
                '<?php echo $key; ?>': <?php echo json_encode($preset['payload']); ?>,
            <?php endforeach; ?>
        };

        const editorTextarea = document.getElementById('wdl_payload');
        const badge = document.getElementById('status-badge');

        function loadPreset(key) {
            if (!presetPayloads[key]) return;
            
            // Update active preset chip class
            document.querySelectorAll('.preset-chip').forEach(chip => {
                chip.classList.remove('active');
            });
            document.getElementById('preset-' + key).classList.add('active');
            
            // Format and update editor value
            editorTextarea.value = JSON.stringify(presetPayloads[key], null, 2);
            compileAndPreview();
        }

        // Live validation handler
        editorTextarea.addEventListener('input', () => {
            const rawVal = editorTextarea.value.trim();
            if (rawVal === '') {
                badge.textContent = 'Empty';
                badge.className = 'status-badge status-error';
                return;
            }
            try {
                JSON.parse(rawVal);
                badge.textContent = 'Valid JSON';
                badge.className = 'status-badge status-success';
            } catch(e) {
                badge.textContent = 'Invalid JSON';
                badge.className = 'status-badge status-error';
            }
        });

        function compileAndPreview() {
            const rawVal = editorTextarea.value.trim();
            try {
                JSON.parse(rawVal);
                badge.textContent = 'Compiling...';
                badge.className = 'status-badge status-success';
                
                document.getElementById('editor-form').submit();
                
                setTimeout(() => {
                    badge.textContent = 'Valid JSON';
                }, 400);
            } catch(e) {
                badge.textContent = 'Invalid JSON';
                badge.className = 'status-badge status-error';
            }
        }

        // Auto-compile default preset on page load
        window.addEventListener('DOMContentLoaded', () => {
            compileAndPreview();
        });
    </script>
</body>
</html>
