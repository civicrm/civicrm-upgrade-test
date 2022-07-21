<?php
namespace Civi\UpgradeTest;

class FindFilterTest extends \PHPUnit\Framework\TestCase {

  public function getExamples(): array {
    $exs = [];

    $exs[] = ['4.0.*', ['4.0.0-setupsh.sql.bz2', '4.0.8-setupsh.sql.bz2']];
    $exs[] = ['@4.3..4.5', ['4.3.0-setupsh.sql.bz2', '4.4.0-setupsh.sql.bz2', '4.4.5-setupsh.sql.bz2']];
    $exs[] = ['@4.1.99', ['4.0.0-setupsh.sql.bz2', '4.0.8-setupsh.sql.bz2', '4.1.0-setupsh.sql.bz2', '4.1.6-setupsh.sql.bz2']];

    return $exs;
  }

  /**
   * @param string|string[] $pattern
   * @param string[] $expectFileNames
   *
   * @dataProvider getExamples
   */
  public function testExamples($pattern, array $expectFileNames): void {
    $actualFilePaths = UpgradeSnapshots::find($pattern);
    foreach ($actualFilePaths as $actualFile) {
      $this->assertTrue(file_exists($actualFile), "Identified file [$actualFile] should exist");
    }
    $actualFileNames = array_map('basename', $actualFilePaths);
    $this->assertEquals($expectFileNames, $actualFileNames);
  }

  public function testMaxCount() {
    $big = UpgradeSnapshots::find('@4.0..4.5');
    $little = UpgradeSnapshots::find('@4.0..4.5:3');
    $this->assertEquals(3, count($little));
    $this->assertTrue(count($big) > count($little));
    $this->assertEquals($little, array_intersect($little, $big), 'All items in $little are also in $big');
  }

}
