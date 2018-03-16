<?php

/**
 * @param string $a
 *   Ex: '@4.5', '@4.2..4.5', '@4.2..', '@4.5:10'
 * @return array
 *   Array with keys 'minVer', 'maxVer', 'maxCount'.
 */
function parseFilterExpr($a) {
  // old: (\.\d+)*
  $dig='(\.(?:\d|alpha|beta)+)*';
//  $a = preg_replace('/\.(alpha|beta)\d*$/', '.0', $a);
  if (preg_match("/^@((\d+$dig)\.\.)?(\d+$dig)?(:(\d+))?$/", $a, $matches)) {
//    print_r(['matches'=>$matches]);
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
