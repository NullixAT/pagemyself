<?php

$packageJson = json_decode(file_get_contents(__DIR__ . "/../package.json"), true);
$changelogLines = file(__DIR__ . "/../CHANGELOG.md");
$logEntries = [];
$valid = false;
$version = $packageJson['version'];

foreach ($changelogLines as $line) {
    if (str_starts_with($line, "# $version")) {
        $valid = true;
        continue;
    }
    if ($valid && str_starts_with($line, "#")) {
        break;
    }
    if ($valid) {
        $logEntries[] = $line;
    }
}

if (!$logEntries) {
    echo "Missing CHANGELOG.md entries for version $version";
    exit(1);
}

file_put_contents(__DIR__ . "/dist/release-log.md", trim(implode("\n", $logEntries)));

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