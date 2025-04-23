#!/usr/bin/env php
<?php
/**
 * @file
 *
 * Scan the `./databases*` folders for new database snapshots.
 * Extract information (such as MySQL version) and store it in `databases.json`.
 */
if (PHP_SAPI !== 'cli') {
  die("update-json.php can only be run from command line.");
}

$project = dirname(__DIR__);
$files = glob("$project/database*/*sql*z*");
$metadataFile = "$project/databases.json";

$oldRecords = file_exists($metadataFile)
  ? json_decode(file_get_contents($metadataFile), TRUE)
  : [];

$newRecords = $oldRecords;
foreach ($files as $f) {
  $id = basename(dirname($f)) . '/' . basename($f);
  if (isset($oldRecords[$id])) {
    continue;
  }

  printf("Found %s\n", $id);

  $cmd = sprintf('bzcat %s | grep -e "-- MySQL dump" | cut -f7 -d\ ', escapeshellarg($f));
  printf("Inspect %s\n", $cmd);
  $mysqlVer = trim(`$cmd`, " \r\n\t,");
  #$mysqlVer = $cmd;

  preg_match('/^(([\d\.]+)-([-\w]+))\.(my)?sql/', basename($f), $m);
  $newRecords[$id] = [
    'set' => basename(dirname($f)),
    'name' => $m[1] ?? NULL,
    'civicrm' => $m[2] ?? NULL,
    'mysql' => $mysqlVer,
    'uf' => preg_match(';standalone;', basename(dirname($f))) ? 'Standalone' : 'Drupal',
    // FIXME: It sould be better to get UF from data. But in practice, everything in 'databases/*' did from D7...
    'keyword' => $m[3] ?? NULL,
  ];
}

ksort($newRecords);
if ($newRecords == $oldRecords) {
  echo "No changes\n";
}
else {
  echo "Update $metadataFile\n";
  $json = json_encode($newRecords, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  file_put_contents($metadataFile, $json);
}
