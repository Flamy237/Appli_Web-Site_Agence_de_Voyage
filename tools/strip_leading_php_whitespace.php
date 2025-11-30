<?php
// Scans PHP files and removes any bytes before the opening '<?php' tag (including BOM).
$root = realpath(__DIR__ . '/../');
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$processed = [];
foreach ($iterator as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (!preg_match('/\.php$/i', $path)) continue;
    // Skip views and public HTML templates to avoid breaking mixed files
    if (preg_match('#/app/views/|/public/|/app/views#', $path)) continue;
    $contents = file_get_contents($path);
    if ($contents === false) continue;
    $pos = strpos($contents, '<?php');
    if ($pos === false) continue; // no php tag (weird)
    if ($pos === 0) {
        // but still check for BOM
        if (strpos($contents, "\xEF\xBB\xBF") === 0) {
            $new = substr($contents, 3);
            file_put_contents($path, $new);
            $processed[] = $path . ' (removed BOM)';
        }
        continue;
    }
    // If there's content before '<?php', remove it
    $newContents = substr($contents, $pos);
    file_put_contents($path, $newContents);
    $processed[] = $path . ' (stripped leading bytes)';
}
if (empty($processed)) {
    echo "No files modified.\n";
} else {
    echo "Modified files:\n" . implode("\n", $processed) . "\n";
}
