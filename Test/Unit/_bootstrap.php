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
 *
 * IMPORTANT -- these are minimal test doubles, not verified reimplementations
 * of Magento's real behavior. Most tests fully mock a stub's methods (via
 * ->method()->willReturn()), so the stub's own body never runs and can't
 * cause a false pass. But a few stubs (e.g. the Template/AbstractBlock/Context
 * chain) are *extended* by real production code under test, so their bodies
 * DO execute for real -- if a stub's behavior diverges from real Magento's
 * (a validation, a thrown exception, a transformation this stub skips), a
 * test can pass here while the same code fails against a real Magento
 * instance. A green run of this suite means "this code is internally
 * consistent," not "this code is verified against real Magento." Anything
 * touching framework behavior nuances still needs manual verification
 * against a real Magento install before merging -- don't let CI passing
 * replace that.
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
