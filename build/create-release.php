<?php

use Framelix\Framelix\Utils\Browser;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Shell;

const FRAMELIX_MODULE = "Myself";
require __DIR__ . "/../modules/Framelix/public/index.php";
require __DIR__ . "/config.php";

$repoRootUrl = 'https://api.github.com/repos/NullixAT/pagemyself';
$browser = Browser::create();
$browser->userPwd = GITHUB_AUTHOKEN;

$packageJson = json_decode(file_get_contents(__DIR__ . "/../package.json"), true);
$changelogLines = explode("\n", str_replace("\r", "", file_get_contents(__DIR__ . "/../CHANGELOG.md")));
$unreleasedLogLines = [];
$valid = false;
$version = $packageJson['version'];


// check if release already exists
$browser->url = $repoRootUrl . "/releases/tags/$version";
$browser->sendRequest();
if (isset($browser->getResponseJson()['assets_url'])) {
    echo "Releaes $version already exists";
    exit(1);
}

$newChangeLogContent = $changelogLines;

$unreleasedEmptyLog = '## [unreleased]

### :heart: Added

### :pencil: Changed

### :construction: Deprecated

### :x: Removed

### :wrench: Fixed

### :police_car: Security';

$date = date("Y-m-d");

$newSectionHeader = '## [' . $version . ' - ' . $date . ']';
$sectionExists = str_contains(implode("\n", $changelogLines), $newSectionHeader);

foreach ($changelogLines as $lineNr => $line) {
    if (($sectionExists && $line === $newSectionHeader) || (!$sectionExists && str_starts_with($line, "## [unreleased]"))) {
        $unreleasedLogLines[] = $newSectionHeader;
        $newChangeLogContent[$lineNr] = $unreleasedEmptyLog . "\n\n$newSectionHeader";
        $valid = true;
        continue;
    }
    if ($valid && str_starts_with($line, "## [")) {
        break;
    }
    if ($valid) {
        $unreleasedLogLines[] = $line;
    }
}


if (!$unreleasedLogLines && !$sectionExists) {
    echo "Missing CHANGELOG.md [unreleased] entries";
    exit(1);
}

$newChangeLogContent = trim(implode("\n", $newChangeLogContent));

$title = $version;
$versionChangelog = trim(implode("\n", $unreleasedLogLines));

// change and commit changelog file
if (!$sectionExists) {
    $changelogFile = __DIR__ . "/../CHANGELOG.md";
    file_put_contents(__DIR__ . "/../CHANGELOG.md", $newChangeLogContent);
    $shell = Shell::prepare('cd {0} && git commit {1} -m {2} && git push', [realpath(__DIR__ . "/.."), $changelogFile, ":robot: updated changelog for release $version"]);
    $shell->execute();
    if ($shell->status) {
        var_dump($shell->output);
        exit(1);
    }
}

// running release script from framelix to build default release package
$framelixBuildFolder = __DIR__ . "/../modules/Framelix/build";
exec("php " . escapeshellarg("$framelixBuildFolder/create-release-package.php"));

$releaseFilename = "release-" . $version . ".zip";
$releaseFile = $framelixBuildFolder . "/dist/$releaseFilename";

if (!file_exists($releaseFile)) {
    echo "Missing $releaseFile";
    exit(1);
}

$browser->url = $repoRootUrl . "/releases";
$browser->requestMethod = 'post';
$browser->requestBody = JsonUtils::encode([
    'tag_name' => $version,
    'name' => $title,
    'body' => $versionChangelog,
    'draft' => true
]);
$browser->sendRequest();
$response = $browser->getResponseJson();

$uploadUrl = $response['upload_url'];
$uploadUrl = preg_replace("~\{.*~", "", $uploadUrl);
$uploadUrl = $uploadUrl . "?name=" . urlencode(basename($releaseFile));

$browser->url = $uploadUrl;
$browser->sendHeaders[] = 'content-type: application/zip';
$browser->requestBody = file_get_contents($releaseFile);
$browser->sendRequest();

$response = $browser->getResponseJson();
if (isset($response['id'])) {
    echo "Release drafted successfully, goto Github, preview and publish it";
} else {
    var_dump($response);
    exit(1);
}