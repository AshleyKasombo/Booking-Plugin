<?php declare(strict_types=1);

if (!\defined('TEST_FILES_PATH')) {
    \define('TEST_FILES_PATH', __DIR__ . \DIRECTORY_SEPARATOR . '_files' . \DIRECTORY_SEPARATOR);
}
\ini_set('precision', '14');
\ini_set('serialize_precision', '14');
require_once __DIR__ . '/../vendor/autoload.php';