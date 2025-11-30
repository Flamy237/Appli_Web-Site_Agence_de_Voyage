<?php
// Utility: scan PHP files for UTF-8 BOM at file start
$root = __DIR__ . '/../';
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$results = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!preg_match('/\.php$/i', $path)) continue;
    $contents = @file_get_contents($path, false, null, 0, 4);
    if ($contents === false) continue;
    $bytes = unpack('C*', $contents);
    if (count($bytes) >= 3 && $bytes[1] == 0xEF && $bytes[2] == 0xBB && $bytes[3] == 0xBF) {
        $results[] = $path;
    }
}
if (empty($results)) {
    echo "No BOM found in PHP files.\n";
    exit(0);
}
echo "Files with UTF-8 BOM:\n";
foreach ($results as $r) echo " - $r\n";
