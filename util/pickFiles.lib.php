<?php
namespace Civi\UpgradeTest;

class UpgradeSnapshots {

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
