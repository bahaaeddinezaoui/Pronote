<?php
// Script to download TCPDF files
$baseDir = __DIR__ . '/vendor/tcpdf';
$fontsDir = $baseDir . '/fonts';
$includeDir = $baseDir . '/include';

// Create directories
if (!is_dir($baseDir)) mkdir($baseDir, 0755, true);
if (!is_dir($fontsDir)) mkdir($fontsDir, 0755, true);
if (!is_dir($includeDir)) mkdir($includeDir, 0755, true);

$files = [
    // Main files
    'tcpdf.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf.php',
    'tcpdf_autoconfig.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf_autoconfig.php',
    'tcpdf_barcodes_1d.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf_barcodes_1d.php',
    'tcpdf_barcodes_2d.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf_barcodes_2d.php',
    'tcpdf_import.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf_import.php',
    'tcpdf_parser.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/tcpdf_parser.php',
    
    // Include files
    'include/tcpdf_colors.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/tcpdf_colors.php',
    'include/tcpdf_fonts.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/tcpdf_fonts.php',
    'include/tcpdf_images.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/tcpdf_images.php',
    'include/tcpdf_static.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/tcpdf_static.php',
    'include/tcpdf_font_data.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/tcpdf_font_data.php',
    
    // Font files (DejaVu Sans - supports Unicode/Arabic)
    'fonts/dejavusans.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/dejavusans.php',
    'fonts/dejavusans.z' => 'https://github.com/tecnickcom/TCPDF/raw/main/fonts/dejavusans.z',
    'fonts/dejavusansb.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/dejavusansb.php',
    'fonts/dejavusansb.z' => 'https://github.com/tecnickcom/TCPDF/raw/main/fonts/dejavusansb.z',
    
    // Helvetica (fallback)
    'fonts/helvetica.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/helvetica.php',
    'fonts/helveticab.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/helveticab.php',
    'fonts/helveticai.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/helveticai.php',
    'fonts/helveticabi.php' => 'https://raw.githubusercontent.com/tecnickcom/TCPDF/main/fonts/helveticabi.php',
];

function downloadFile($url, $path) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'follow_location' => true,
            'max_redirects' => 5
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $data = @file_get_contents($url, false, $context);
    if ($data === false) {
        echo "Failed to download: $url\n";
        return false;
    }
    
    file_put_contents($path, $data);
    echo "Downloaded: $path (" . strlen($data) . " bytes)\n";
    return true;
}

echo "<pre>";
echo "Starting TCPDF installation...\n\n";

foreach ($files as $localPath => $url) {
    $fullPath = $baseDir . '/' . $localPath;
    $dir = dirname($fullPath);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    downloadFile($url, $fullPath);
}

echo "\nInstallation complete!\n";
echo "</pre>";
