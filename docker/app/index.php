<?php

// download latest release, unpack and link to install.php
$context = stream_context_create([
        'http' => [
            'method' => "GET",
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36'
        ]
    ]
);
$releaseData = json_decode(
    file_get_contents(
        'https://api.github.com/repos/NullixAT/pagemyself/releases/latest',
        false,
        $context
    ),
    true
);
$zipFile = __DIR__ . "/release.zip";
file_put_contents($zipFile, file_get_contents($releaseData['assets'][0]['browser_download_url'], 0, $context));

$zipArchive = new ZipArchive();
$openResult = $zipArchive->open($zipFile, ZipArchive::RDONLY);
if ($openResult !== true) {
    throw new Exception("Cannot open ZIP File '$zipFile' ($openResult)");
}
$zipArchive->extractTo(__DIR__);
$zipArchive->close();
if (file_exists(__DIR__ . "/install.php")) {
    unlink($zipFile);
    unlink(__FILE__);
    header("location: install.php");
}