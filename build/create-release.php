<?php

use Framelix\Framelix\Utils\Zip;

const FRAMELIX_MODULE = "Framelix";
require_once __DIR__ . "/../modules/Framelix/public/index.php";

// running release script from framelix to build default release package
$framelixBuildFolder = __DIR__ . "/../modules/Framelix/build";
exec("php " . escapeshellarg("$framelixBuildFolder/create-release-package.php"));

$releaseFilename = null;
$files = scandir($framelixBuildFolder . "/dist");
foreach ($files as $file) {
    if (str_starts_with($file, "release-") && str_ends_with($file, ".zip")) {
        $targetFile = __DIR__ . "/dist/" . $file;
        if (file_exists($targetFile)) {
            unlink($targetFile);
        }
        rename($framelixBuildFolder . "/dist/$file", $targetFile);
        $releaseFilename = $file;
        break;
    }
}

// build docker release file
$dockerZip = __DIR__ . "/dist/" . substr($releaseFilename, 0, -4) . "-docker.zip";
$dockerFolder = __DIR__ . "/../pagemyself_docker";
$dockerZipFiles = [
    "app/index.php" => $dockerFolder . "/app/index.php",
    "db" => $dockerFolder . "/db",
    "cronjobs" => $dockerFolder . "/cronjobs",
    "docker-compose.yml" => $dockerFolder . "/docker-compose.yml",
    "Dockerfile" => $dockerFolder . "/Dockerfile",
    ".env" => $dockerFolder . "/env-default",
    "nginx-config.conf" => $dockerFolder . "/nginx-config.conf",
    "php-fpm.conf" => $dockerFolder . "/php-fpm.conf",
    "php-fpm-entrypoint.sh" => $dockerFolder . "/php-fpm-entrypoint.sh"
];
Zip::createZip($dockerZip, $dockerZipFiles);