<?php
namespace Civi\UpgradeTest;

class ParseFilterTest extends \PHPUnit\Framework\TestCase {

  public function testMisc() {
    $this->assertFilterExpr(['minVer' => '', 'maxVer' => '4.5', 'maxCount' => NULL], '@4.5');
    $this->assertFilterExpr(['minVer' => '', 'maxVer' => '4.5', 'maxCount' => '13'], '@4.5:13');
    $this->assertFilterExpr(['minVer' => '', 'maxVer' => '4.5.2', 'maxCount' => NULL], '@4.5.2');
    $this->assertFilterExpr(['minVer' => '4.5.2', 'maxVer' => NULL, 'maxCount' => NULL], '@4.5.2..');
    $this->assertFilterExpr(['minVer' => '4.6.alpha1', 'maxVer' => NULL, 'maxCount' => NULL], '@4.6.alpha1..');
    $this->assertFilterExpr(['minVer' => '4.5.2', 'maxVer' => NULL, 'maxCount' => '7'], '@4.5.2..:7');
    $this->assertFilterExpr(['minVer' => '4.3.1', 'maxVer' => '4.6', 'maxCount' => NULL], '@4.3.1..4.6');
    $this->assertFilterExpr(['minVer' => '4.3', 'maxVer' => '4.6.10', 'maxCount' => NULL], '@4.3..4.6.10');
    $this->assertFilterExpr(['minVer' => '4.3', 'maxVer' => '4.6.10', 'maxCount' => '9'], '@4.3..4.6.10:9');
    $this->assertFilterExpr(['minVer' => '', 'maxVer' => '5.0.alpha1', 'maxCount' => NULL], '@5.0.alpha1');
    $this->assertFilterExpr(['minVer' => '5.0.alpha1', 'maxVer' => '5.0.beta2', 'maxCount' => NULL], '@5.0.alpha1..5.0.beta2');
    $this->assertFilterExpr(['minVer' => '5.0.alpha1', 'maxVer' => '5.0.beta2', 'maxCount' => '19'], '@5.0.alpha1..5.0.beta2:19');
  }

  protected function assertFilterExpr($expected, $filterExpr) {
    $parsed = UpgradeExamples::instance()->parseFilterExpr($filterExpr);
    $this->assertEquals($expected, $parsed, "Parsing \"$filterExpr\": ");
  }

}
