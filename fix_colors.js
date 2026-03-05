const fs = require('fs');
const path = require('path');

function processFile(filePath) {
    if (!fs.existsSync(filePath)) return;

    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;

    // 1. Add FOUC script to <head> if not present
    if (!content.includes('edutrack_theme') && /<head>/i.test(content)) {
        content = content.replace(/(<head>)/i, "$1\n    <script>if(localStorage.getItem('edutrack_theme')==='dark') document.documentElement.setAttribute('data-theme', 'dark');</script>");
    }

    // 2. Global Color Replacements from styles.css (but targeting inline styles in HTML/PHP)

    // Background light gray variations (surface -> background-color)
    content = content.replace(/(background(?:-color)?:\s*)(?:#f9fafb)(;|"| |\}|')/gi, '$1var(--background-color)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#fff|#ffffff|white)(;|"| |\}|')/gi, '$1var(--surface-color)$2');

    // Borders
    content = content.replace(/(border(?:-top|-right|-bottom|-left)?:\s*[^;"]*)(?:#bbb|#e5e7eb|#e2e8f0|#e6e6e6|#ddd|#ccc)(;|"| |\}|')/gi, '$1var(--border-color)$2');
    content = content.replace(/(border-color:\s*)(?:#bbb|#e5e7eb|#e2e8f0|#e6e6e6|#ddd|#ccc)(;|"| |\}|')/gi, '$1var(--border-color)$2');

    // Text colors
    content = content.replace(/(color:\s*)(?:#333|#444|#1f1f1f|#000|black)(;|"| |\}|')/gi, '$1var(--text-primary)$2');
    content = content.replace(/(color:\s*)(?:#666|#888)(;|"| |\}|')/gi, '$1var(--text-secondary)$2');

    // Semantic Backgrounds (Alerts, Badges)
    content = content.replace(/(background(?:-color)?:\s*)(?:#dcfce7|#d4edda|#ecfdf5)(;|"| |\}|')/gi, '$1var(--bg-success)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#fee2e2|#f8d7da|#fffafa|#fef2f2)(;|"| |\}|')/gi, '$1var(--bg-error)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#fef3c7|#fffdf5|#f59e0b)(;|"| |\}|')/gi, '$1var(--bg-warning)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#eff6ff|#e0f2fe|#f1f8ff)(;|"| |\}|')/gi, '$1var(--bg-info)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#f3f4f6|#f8f9fa|#f7f9fc|#fafafa)(;|"| |\}|')/gi, '$1var(--bg-muted)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#e5e7eb|#d1d5db|#eee|#f1f1f1)(;|"| |\}|')/gi, '$1var(--border-color)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#f5f3ff|#ede9fe|#ddd6fe)(;|"| |\}|')/gi, '$1rgba(139, 92, 246, 0.15)$2');

    // Semantic Text Colors
    content = content.replace(/(color:\s*)(?:#166534|#155724|#10b981)(;|"| |\}|')/gi, '$1var(--text-success)$2');
    content = content.replace(/(color:\s*)(?:#991b1b|#721c24|#ef4444)(;|"| |\}|')/gi, '$1var(--text-error)$2');
    content = content.replace(/(color:\s*)(?:#92400e)(;|"| |\}|')/gi, '$1var(--text-warning)$2');
    content = content.replace(/(color:\s*)(?:#1e40af)(;|"| |\}|')/gi, '$1var(--text-info)$2');
    content = content.replace(/(color:\s*)(?:#4b5563)(;|"| |\}|')/gi, '$1var(--text-muted)$2');

    // Semantic Border Colors
    content = content.replace(/(border(?:-left)?(?:-color)?:\s*)(?:#bbf7d0|#c3e6cb)(;|"| |\}|')/gi, '$1var(--border-success)$2');
    content = content.replace(/(border(?:-left)?(?:-color)?:\s*)(?:#fecaca|#f5c6cb)(;|"| |\}|')/gi, '$1var(--border-error)$2');
    content = content.replace(/(border(?:-left)?(?:-color)?:\s*)(?:#fde68a|#ffb74d)(;|"| |\}|')/gi, '$1var(--border-warning)$2');
    content = content.replace(/(border(?:-left)?(?:-color)?:\s*)(?:#bfdbfe|#64b5f6)(;|"| |\}|')/gi, '$1var(--border-info)$2');

    if (content !== originalContent) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log(`Updated ${filePath}`);
    }
}

function traverseDir(dir) {
    fs.readdirSync(dir).forEach(file => {
        let fullPath = path.join(dir, file);
        if (fs.lstatSync(fullPath).isDirectory()) {
            if (file !== '.git' && file !== 'node_modules') {
                traverseDir(fullPath);
            }
        } else {
            if (fullPath.endsWith('.php') || fullPath.endsWith('.html')) {
                processFile(fullPath);
            }
        }
    });
}

traverseDir(__dirname);
