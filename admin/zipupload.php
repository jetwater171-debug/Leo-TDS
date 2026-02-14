<?php
require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');

function zip_error(string $msg): void
{
    echo json_encode(['error' => true, 'result' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    zip_error('Only POST allowed');
}

if (empty($_FILES['zipfile']) || $_FILES['zipfile']['error'] !== UPLOAD_ERR_OK) {
    zip_error('No file uploaded or upload error');
}

$folder = $_POST['folder'] ?? '';
$folder = trim($folder);

// Sanitize: only alphanumeric, hyphens, underscores, dots
if (!preg_match('/^[a-zA-Z0-9_\-\.]+$/', $folder)) {
    zip_error('Invalid folder name. Use only letters, numbers, hyphens, underscores, dots.');
}

global $cloSettings;
$landingDir = realpath(__DIR__ . '/../' . $cloSettings['landingFolder']);
if ($landingDir === false) {
    // Try to create it
    @mkdir(__DIR__ . '/../' . $cloSettings['landingFolder'], 0755, true);
    $landingDir = realpath(__DIR__ . '/../' . $cloSettings['landingFolder']);
    if ($landingDir === false) {
        zip_error('Landing folder does not exist and could not be created');
    }
}

$targetDir = $landingDir . DIRECTORY_SEPARATOR . $folder;

if (file_exists($targetDir)) {
    zip_error('Folder "' . $folder . '" already exists. Choose a different name or delete it first.');
}

// Open and validate ZIP
$zip = new ZipArchive();
$res = $zip->open($_FILES['zipfile']['tmp_name']);
if ($res !== true) {
    zip_error('Cannot open ZIP archive (error code: ' . $res . ')');
}

// Analyze root structure
$rootFiles = [];
$rootDirs = [];
for ($i = 0; $i < $zip->numFiles; $i++) {
    $name = $zip->getNameIndex($i);
    // Skip __MACOSX and hidden files
    if (str_starts_with($name, '__MACOSX') || str_starts_with($name, '.')) {
        continue;
    }
    $parts = explode('/', $name);
    if (count($parts) === 1 && $name !== '') {
        // Root file
        $rootFiles[] = $name;
    } elseif (count($parts) >= 2 && $parts[0] !== '') {
        $rootDirs[$parts[0]] = true;
        // If it's a file directly inside the first-level dir
        if (count($parts) === 2 && $parts[1] !== '') {
            // track
        }
    }
}
$rootDirs = array_keys($rootDirs);

// Determine extraction mode
$hasRootIndex = in_array('index.php', $rootFiles) || in_array('index.html', $rootFiles);
$singleDirMode = false;
$singleDirName = '';

if (!$hasRootIndex) {
    // Check if there's exactly one root directory
    if (count($rootDirs) === 1 && count($rootFiles) === 0) {
        $singleDirName = $rootDirs[0];
        // Check if index.php or index.html exists inside that directory
        $hasInnerIndex = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name === $singleDirName . '/index.php' || $name === $singleDirName . '/index.html') {
                $hasInnerIndex = true;
                break;
            }
        }
        if ($hasInnerIndex) {
            $singleDirMode = true;
        } else {
            $zip->close();
            zip_error('ZIP invalid: the folder "' . $singleDirName . '" does not contain index.php or index.html');
        }
    } else {
        $zip->close();
        zip_error('ZIP invalid: no index.php or index.html found at root level');
    }
}

// Create target directory
if (!mkdir($targetDir, 0755, true)) {
    $zip->close();
    zip_error('Failed to create target directory');
}

// Extract
if ($singleDirMode) {
    // Extract contents of the single directory to target
    $prefixLen = strlen($singleDirName) + 1; // +1 for the slash
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_starts_with($name, '__MACOSX') || str_starts_with($name, '.')) {
            continue;
        }
        if (!str_starts_with($name, $singleDirName . '/')) {
            continue;
        }
        $relativePath = substr($name, $prefixLen);
        if ($relativePath === '' || $relativePath === false) {
            continue;
        }
        $destPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        // Security: ensure we stay within target
        if (str_contains($relativePath, '..')) {
            continue;
        }

        if (str_ends_with($name, '/')) {
            // Directory
            @mkdir($destPath, 0755, true);
        } else {
            // File
            $dir = dirname($destPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($destPath, $content);
            }
        }
    }
} else {
    // Extract everything directly (skip __MACOSX)
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (str_starts_with($name, '__MACOSX') || str_starts_with($name, '.')) {
            continue;
        }
        if (str_contains($name, '..')) {
            continue;
        }
        $destPath = $targetDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $name);

        if (str_ends_with($name, '/')) {
            @mkdir($destPath, 0755, true);
        } else {
            $dir = dirname($destPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $content = $zip->getFromIndex($i);
            if ($content !== false) {
                file_put_contents($destPath, $content);
            }
        }
    }
}

$zip->close();

echo json_encode(['error' => false, 'folder' => $folder, 'result' => 'OK']);
