<?php

use Framelix\Framelix\Utils\FileUtils;
use Framelix\Framelix\Utils\JsonUtils;
use Framelix\Framelix\Utils\Zip;

const FRAMELIX_MODULE = "Framelix";
require_once __DIR__ . "/../modules/Framelix/public/index.php";

$module = $_SERVER['argv'][1] ?? "-";
$modulePath = __DIR__ . "/../modules/$module";
if (!is_dir($modulePath)) {
    echo "First command parameter must be a valid module name";
    exit;
}

$modulePath = FileUtils::normalizePath(realpath($modulePath));

$ignoreModuleFiles = [
    "^/.(git|svn|idea)",
    "^/config/config-editable.php$",
    "^/(dev|js|nodejs|node_modules|scss|tests|tmp)",
    "^/package-lock\.json",
];
$arr = [];
$modulePackageJson = JsonUtils::readFromFile($modulePath . "/package.json");
$files = FileUtils::getFiles($modulePath, null, true, true);
$ignoreArr = $ignoreModuleFiles;
if (isset($modulePackageJson['pagemyself']['release']['exclude'])) {
    $ignoreArr = array_merge($ignoreArr, $modulePackageJson['pagemyself']['release']['exclude']);
}
foreach ($files as $file) {
    $relativeName = substr($file, strlen($modulePath));
    if (!str_ends_with($file, ".gitignore")) {
        foreach ($ignoreArr as $ignoreFileRegex) {
            if (preg_match("~$ignoreFileRegex~i", $relativeName)) {
                continue 2;
            }
        }
    }
    $directories = explode("/", substr($relativeName, 1));
    if (count($directories) > 1) {
        array_pop($directories);
        $directoryPath = [];
        foreach ($directories as $directory) {
            $directoryPath[] = $directory;
            $dir = implode("/", $directoryPath);
            $arr[$dir] = $modulePath . "/" . $dir;
        }
    }
    $arr[substr($relativeName, 1)] = $file;
}
$filelistPath = $modulePath . "/filelist.json";
$oldFileList = file_exists($filelistPath) ? file_get_contents($filelistPath) : null;
JsonUtils::writeToFile($filelistPath, array_keys($arr));
$arr["filelist.json"] = $filelistPath;
$zipFile = FileUtils::normalizePath(
    __DIR__ . "/dist/$module-" . $modulePackageJson['version'] . ".zip"
);
Zip::createZip($zipFile, $arr);
@unlink($filelistPath);
if ($oldFileList) {
    file_put_contents($filelistPath, $oldFileList);
}
echo $zipFile;