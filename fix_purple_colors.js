const fs = require('fs');
const path = require('path');

function processFile(filePath) {
    if (!fs.existsSync(filePath)) return;

    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;

    // Replace primary purple colors with the primary CSS variable
    content = content.replace(/(color:\s*)(?:#6f42c1|#6d28d9|#5b21b6|#4f46e5)(;|"| |\}|')/gi, '$1var(--primary-color)$2');

    // Also replace them in border colors and background colors
    content = content.replace(/(border(?:-color)?:\s*)(?:#6f42c1|#6d28d9|#5b21b6)(;|"| |\}|')/gi, '$1var(--primary-color)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#6f42c1|#6d28d9|#5b21b6)(;|"| |\}|')/gi, '$1var(--primary-color)$2');

    // Primary Hover Colors
    content = content.replace(/(color:\s*)(?:#5a34a1|#4338ca)(;|"| |\}|')/gi, '$1var(--primary-hover)$2');
    content = content.replace(/(background(?:-color)?:\s*)(?:#5a34a1|#4338ca)(;|"| |\}|')/gi, '$1var(--primary-hover)$2');
    content = content.replace(/(border(?:-color)?:\s*)(?:#5a34a1|#4338ca)(;|"| |\}|')/gi, '$1var(--primary-hover)$2');

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
            if (fullPath.endsWith('.php') || fullPath.endsWith('.html') || fullPath.endsWith('.css')) {
                processFile(fullPath);
            }
        }
    });
}

traverseDir(__dirname);
