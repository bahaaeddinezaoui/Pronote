const fs = require('fs');
const path = require('path');

function processFile(filePath) {
    if (!fs.existsSync(filePath)) return;

    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;

    // Replace dark grays with text-primary
    content = content.replace(/(color:\s*)(?:#111827|#1f2937|#374151|#111|#222|#333)(;|"| |\}|')/gi, '$1var(--text-primary)$2');

    // Replace medium/light grays with text-secondary
    content = content.replace(/(color:\s*)(?:#6b7280|#444|#555)(;|"| |\}|')/gi, '$1var(--text-secondary)$2');

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
