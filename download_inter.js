const https = require('https');
const fs = require('fs');
const path = require('path');

const fontsDir = path.join(__dirname, 'assets', 'fonts');
if (!fs.existsSync(fontsDir)) {
    fs.mkdirSync(fontsDir, { recursive: true });
}

const cssUrl = 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap';

console.log('Fetching Google Fonts CSS...');
https.get(cssUrl, {
    headers: {
        'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36'
    }
}, (res) => {
    let cssData = '';
    res.on('data', chunk => cssData += chunk);
    res.on('end', () => {
        const urlRegex = /url\((https:\/\/[^)]+)\)/g;
        let match;
        let downloads = [];
        let newCssData = cssData;
        let fontCounter = 1;

        while ((match = urlRegex.exec(cssData)) !== null) {
            const fontUrl = match[1];
            const fileName = `inter-${fontCounter++}.woff2`;
            const filePath = path.join(fontsDir, fileName);
            downloads.push({ url: fontUrl, filePath, fileName });

            // Replace the remote URL with a local one relative to styles.css
            newCssData = newCssData.replace(fontUrl, `assets/fonts/${fileName}`);
        }

        console.log(`Found ${downloads.length} font files to download.`);
        if (downloads.length === 0) {
            console.error('No fonts found to download. Check the CSS URL and User-Agent.');
            return;
        }

        let completed = 0;
        downloads.forEach(d => {
            https.get(d.url, (fontRes) => {
                const fileStream = fs.createWriteStream(d.filePath);
                fontRes.pipe(fileStream);
                fileStream.on('finish', () => {
                    fileStream.close();
                    completed++;
                    if (completed === downloads.length) {
                        console.log('All font files downloaded.');

                        // Replace @import in styles.css
                        const stylesPath = path.join(__dirname, 'styles.css');
                        let stylesContent = fs.readFileSync(stylesPath, 'utf8');

                        // Find the precise @import statement
                        const importRegex = /@import url\('https:\/\/fonts\.googleapis\.com\/css2\?family=Inter[^)]+'\);\s*/;
                        if (importRegex.test(stylesContent)) {
                            // Prepend the new @font-face rules
                            stylesContent = stylesContent.replace(importRegex, '/* Local Inter Font */\n' + newCssData + '\n\n');
                            fs.writeFileSync(stylesPath, stylesContent, 'utf8');
                            console.log('styles.css updated to use local fonts.');
                        } else {
                            console.log('Could not find the @import statement in styles.css.');
                        }
                    }
                });
            }).on('error', (err) => {
                console.error(`Error downloading ${d.url}:`, err.message);
            });
        });
    });
}).on('error', (err) => {
    console.error('Error fetching CSS:', err.message);
});
