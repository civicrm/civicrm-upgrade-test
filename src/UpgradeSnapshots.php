<?php
namespace Civi\UpgradeTest;

class UpgradeSnapshots {

  /**
   * Get the base-path of the snapshot data.
   *
   * @return string
   */
  public static function getPath(): string {
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'databases';
  }

  public static function getAll(): array {
    $dbDir = static::getPath() . DIRECTORY_SEPARATOR;
    $allFiles = array_merge(
      (array) glob("{$dbDir}*.sql.bz2"),
      (array) glob("{$dbDir}*.sql.gz"),
      (array) glob("{$dbDir}*.mysql.bz2"),
      (array) glob("{$dbDir}*.mysql.gz")
    );
    return $allFiles;
  }

  /**
   * Find all known snapshots that match a filter-expression.
   *
   * @param string|string[] $filters
   *   List of filter expressions.
   *   Ex: '4.*', '5.0.*'
   *   Ex: '@4.5', '@4.2..4.5', '@4.2..', '@4.5:10'
   * @return string[]
   *   List of snapshots (file-paths).
   */
  public static function find($filters): array {
    $allFiles = UpgradeSnapshots::getAll();

    $filters = (array) $filters;
    $files = [];

    foreach ($filters as $arg) {
      if ($arg[0] === '@') {
        $parsedFilter = UpgradeSnapshots::parseFilterExpr($arg);

        $matches = array_filter($allFiles, function($f) use ($parsedFilter) {
          $fileVer = UpgradeSnapshots::parseFileVer($f);
          if ($parsedFilter['minVer'] && version_compare($fileVer, $parsedFilter['minVer'], '<=')) {
            return FALSE;
          }
          if ($parsedFilter['maxVer'] && version_compare($fileVer, $parsedFilter['maxVer'], '>=')) {
            return FALSE;
          }
          return TRUE;
        });

        if ($parsedFilter['maxCount'] > 0) {
          $matches = UpgradeSnapshots::pickSubset($matches, $parsedFilter['maxCount']);
        }

        $files = array_merge($files, $matches);
      }
      elseif (strpos($arg, '*')) {
        $files = array_merge($files,
          (array) glob(UpgradeSnapshots::getPath() . DIRECTORY_SEPARATOR . $arg));
      }
      else {
        throw new \InvalidArgumentException("Unrecognized filter: $arg");
      }
    }

    return $files;
  }

  /**
   * @param string $a
   *   Filter expression that selects range of test examples.
   *   Ex: '@4.5', '@4.2..4.5', '@4.2..', '@4.5:10'
   *   Should match this formula:
   *     '@' [MinVersion '..'] MaxVersion [":" MaxCount]
   * @return array
   *   Array with keys 'minVer', 'maxVer', 'maxCount'.
   *   Ex: ['minVer' => 4.5, 'maxVer' => NULL, 'maxCount' => 10]
   */
  public static function parseFilterExpr(string $a): array {
    // old: (\.\d+)*
    $dig = '(\.(?:\d|alpha|beta)+)*';
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

  /**
   * @param string[] $files
   * @return string[]
   *   List of files, re-sorted.
   */
  public static function sortFilesByVer(array $files): array {
    $files = array_unique($files);
    usort($files, function($a, $b) {
      return version_compare(UpgradeSnapshots::parseFileVer($a), UpgradeSnapshots::parseFileVer($b));
    });
    return $files;
  }

  /**
   * Extract the version-part of a filename.
   *
   * @param string $file
   *   Ex: '/path/to/4.2.0-setupsh.sql.bz2'.
   * @return string
   *   Ex: '4.2.0'.
   */
  public static function parseFileVer(string $file): string {
    $name = basename($file);
    $parts = explode('-', $name);
    return $parts[0];
  }

  /**
   * Extract the "major.minor" from a version expression.
   *
   * @param string $ver
   *   Ex: '4.2', '4.2.10'.
   * @return string
   *   Ex: '4.2'
   */
  public static function parseMajorMinor(string $ver): string {
    [$a, $b] = explode('.', $ver);
    return "$a.$b";
  }

  /**
   * Pick a subset of elements, including the first element,
   * last element, and random in-between. Try to avoid
   * picking multiple items in the same series.
   *
   * @param string[] $files
   * @param int $maxCount
   * @return string[]
   */
  public static function pickSubset($files, $maxCount) {
    $files = UpgradeSnapshots::sortFilesByVer($files);

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

    $allMajorMinors = array_unique(array_map(function($s) {
      return UpgradeSnapshots::parseMajorMinor(UpgradeSnapshots::parseFileVer($s));
    }, $files));
    $allowDupeMajorMinor = FALSE;
    while ($maxCount > 0) {
      $i = rand(0, count($files) - 1);

      $selectedMajorMinors = array_unique(array_map(function($s) {
        return UpgradeSnapshots::parseMajorMinor(UpgradeSnapshots::parseFileVer($s));
      }, $selections));
      $hasAllMajorMinor = count(array_diff($allMajorMinors, $selectedMajorMinors)) == 0;
      $myMajorMinor = UpgradeSnapshots::parseMajorMinor(UpgradeSnapshots::parseFileVer($files[$i]));
      if (!$hasAllMajorMinor && in_array($myMajorMinor, $selectedMajorMinors)) {
        continue;
      }

      $selections[] = $files[$i];
      unset($files[$i]);
      $files = array_values($files);
      $maxCount--;
    }

    return UpgradeSnapshots::sortFilesByVer($selections);
  }

}
