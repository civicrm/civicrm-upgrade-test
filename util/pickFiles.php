#!/usr/bin/env php
<?php
namespace Civi\UpgradeTest;

main($argv);

function help($error = NULL) {
  if ($error) {
    fwrite(STDERR, $error);
  }
  echo "usage: pickFiles.php <fileExpr> ...\n";
  echo "\n";
  echo "<fileExpr> examples:\n";
  echo "  /var/foo.sql.gz  A specific file\n";
  echo "  4.5*             All files in the default dir beginning with '4.2'\n";
  echo "  @4.5             All files in the default dir which are less than 4.5\n";
  echo "  @4.5.10          All files in the default dir which are less than 4.5.10\n";
  echo "  @4.2..5.1.0     All files starting at 4.2 and before 5.1.0\n";
  echo "\n";
}

function main($args) {
  require_once __DIR__ . DIRECTORY_SEPARATOR . 'pickFiles.lib.php';
  $prog = array_shift($args);
  if (empty($args)) {
    help("Missing required argument <fileExpr>\n");
    return 1;
  }

  $dbDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'databases' . DIRECTORY_SEPARATOR;
  $allFiles = array_merge(
    (array) glob("{$dbDir}*.sql.bz2"),
    (array) glob("{$dbDir}*.sql.gz"),
    (array) glob("{$dbDir}*.mysql.bz2"),
    (array) glob("{$dbDir}*.mysql.gz")
  );

  $files = array();
  $maxCount = 0;
  while (!empty($args)) {
    $arg = array_shift($args);

    if ($arg[0] === '-') {
      help("Unrecognized option: $arg\n");
      return 2;
    }
    elseif ($arg === '') {
      // skip
    }
    elseif (file_exists($arg)) {
      $files[] = $arg;
    }
    elseif ($arg[0] === '@') {
      $filters = parseFilterExpr($arg);

      $matches = array_filter($allFiles, function($f) use ($filters) {
        $fileVer = parseFileVer($f);
        if ($filters['minVer'] && version_compare($fileVer, $filters['minVer'], '<=')) {
          return FALSE;
        }
        if ($filters['maxVer'] && version_compare($fileVer, $filters['maxVer'], '>=')) {
          return FALSE;
        }
        return TRUE;
      });

      if ($filters['maxCount'] > 0) {
        $matches = pickSubset($matches, $filters['maxCount']);
      }

      $files = array_merge($files, $matches);
    }
    elseif (strpos($arg, '*')) {
      $files = array_merge(
        $files,
        (array) glob($dbDir . $arg)
      );
    }
    else {
      help("Unrecognized argument or missing file: $arg\n");
      exit(3);
    }
  }

  $files = sortFilesByVer(array_unique($files));
  foreach ($files as $file) {
    echo "$file\n";
  }
}
