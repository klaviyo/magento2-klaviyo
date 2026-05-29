<?php

/**
 * PHPUnit bootstrap.
 *
 * Loads composer's autoloader first, then pre-loads every stub file under
 * Test/Unit/_stubs/. The stubs declare stand-in classes/interfaces for
 * Magento framework symbols that aren't installed in the local test
 * environment, each guarded by class_exists/interface_exists/function_exists
 * with $autoload=false so they no-op when the real Magento autoloader is
 * present (e.g. inside the docker container).
 *
 * Stubs live one-class-per-file in a directory tree mirroring their
 * namespace, so PSR1.Classes.ClassDeclaration.MultipleClasses doesn't
 * fire when phpcs scans Test/.
 */

// Locate the composer autoloader. Two valid layouts:
//   - Repo-local install (CI, local dev):  <repo>/vendor/autoload.php
//   - Installed inside Magento:            <magento-root>/vendor/autoload.php
$autoloadCandidates = [
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../../vendor/autoload.php',
];
foreach ($autoloadCandidates as $candidate) {
    if (file_exists($candidate)) {
        require $candidate;
        break;
    }
}

$stubsRoot = __DIR__ . '/_stubs';
if (is_dir($stubsRoot)) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stubsRoot));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            require_once $file->getPathname();
        }
    }
}
