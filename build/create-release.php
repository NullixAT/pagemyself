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
$changelogFile = __DIR__ . "/../CHANGELOG.md";
$existingChangelog = file_get_contents($changelogFile);
$unreleasedChangelog = file_get_contents(__DIR__ . "/../CHANGELOG_unreleased.md");
if (!$unreleasedChangelog) {
    echo "No changelog content in CHANGELOG_unreleased.md";
    exit(1);
}
$version = $packageJson['version'];

// check if release already exists
$browser->url = $repoRootUrl . "/releases/tags/$version";
$browser->sendRequest();
if (isset($browser->getResponseJson()['assets_url'])) {
    echo "Releaes $version already exists";
    exit(1);
}

$date = date("Y-m-d");

$addChangelog = '## [' . $version . ' - ' . $date . ']';

if (!str_contains($existingChangelog, $addChangelog)) {
    // change and commit changelog file
    file_put_contents(
        $changelogFile,
        $addChangelog . "\n\n" . $unreleasedChangelog . "\n\n" . $existingChangelog
    );
    $shell = Shell::prepare(
        'cd {0} && git commit {1} -m {2} && git push',
        [realpath(__DIR__ . "/.."), $changelogFile, ":robot: updated changelog for release $version"]
    );
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
    'name' => $version,
    'body' => $unreleasedChangelog,
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