#!/usr/bin/env php
<?php

main($argv);

function main($args) {
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

    if ($arg{0} === '-') {
      help("Unrecognized option: $arg\n");
      return 2;
    }
    elseif ($arg === '') {
      // skip
    }
    elseif (file_exists($arg)) {
      $files[] = $arg;
    }
    elseif ($arg{0} === '@') {
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

/**
 * @param string $a
 *   Ex: '@4.5', '@4.2..4.5', '@4.2..', '@4.5:10'
 * @return array
 *   Array with keys 'minVer', 'maxVer', 'maxCount'.
 */
function parseFilterExpr($a) {
  if (preg_match('/^@((\d+(\.\d+)*)\.\.)?(\d+(\.\d+)*)?(:(\d+))?$/', $a, $matches)) {
    return array(
      'minVer' => isset($matches[2]) ? $matches[2] : NULL,
      'maxVer' => isset($matches[4]) ? $matches[4] : NULL,
      'maxCount' => isset($matches[7]) ? $matches[7] : NULL,
    );
  }
  else {
    throw new \RuntimeException("Malformed filter expression: $a");
  }
}

function sortFilesByVer($files) {
  $files = array_unique($files);
  usort($files, function($a, $b) {
    return version_compare(parseFileVer($a), parseFileVer($b));
  });
  return $files;
}

/**
 * @param string $file
 *   Ex: '/path/to/4.2.0-setupsh.sql.bz2'.
 * @return string
 *   Ex: '4.2.0'.
 */
function parseFileVer($file) {
  $name = basename($file);
  $parts = explode('-', $name);
  return $parts[0];
}

/**
 * @param string $ver
 *   Ex: '4.2', '4.2.10'.
 * @return string
 *   Ex: '4.2'
 */
function parseMajorMinor($ver) {
  list ($a, $b) = explode('.', $ver);
  return "$a.$b";
}

/**
 * Pick a subset of elements, including the first element,
 * last element, and random in-between. Try to avoid
 * picking multiple items in the same series.
 */
function pickSubset($files, $maxCount) {
  $files = sortFilesByVer($files);

  if ($maxCount >= count($files)) {
    return $files;
  }

  $selections = array();
  if ($maxCount > 0) {
    $selections[] = array_shift($files);
    $maxCount--;
  }

  if ($maxCount > 0) {
    $selections[] = array_pop($files);
    $maxCount--;
  }

  $allMajorMinors = array_unique(array_map(function($s){
    return parseMajorMinor(parseFileVer($s));
  }, $files));
  $allowDupeMajorMinor = FALSE;
  while ($maxCount > 0) {
    $i = rand(0, count($files) - 1);

    $selectedMajorMinors = array_unique(array_map(function($s){
      return parseMajorMinor(parseFileVer($s));
    }, $selections));
    $hasAllMajorMinor = count(array_diff($allMajorMinors, $selectedMajorMinors)) == 0;
    $myMajorMinor = parseMajorMinor(parseFileVer($files[$i]));
    if (!$hasAllMajorMinor && in_array($myMajorMinor, $selectedMajorMinors)) {
      continue;
    }

    $selections[] = $files[$i];
    unset($files[$i]);
    $files = array_values($files);
    $maxCount--;
  }

  return sortFilesByVer($selections);
}

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
