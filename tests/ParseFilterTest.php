<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'util' . DIRECTORY_SEPARATOR . 'pickFiles.lib.php';
global $assertionCount;
$assertionCount = ['ok' => 0, 'fail' => 0];

function assertEquals($expected, $actual, $message = '') {
  global $assertionCount;

  if (is_array($expected)) {
    ksort($expected);
  }
  if (is_array($actual)) {
    ksort($actual);
  }

  if ($expected != $actual) {
    fwrite(STDERR, $message . var_export(['Values are not equals', 'expected' => $expected, 'actual' => $actual], 1));
    $assertionCount['fail']++;
  }
  else {
    $assertionCount['ok']++;
  }
}

function assertFilterExpr($expected, $filterExpr) {
  global $assertionCount;
  try {
    $parsed = parseFilterExpr($filterExpr);
  }
  catch (RuntimeException $e) {
    printf("Exception: %s\n", $e->getMessage());
    $assertionCount['fail']++;
    return;
  }
  assertEquals($expected, $parsed, "Parsing \"$filterExpr\": ");
}

assertFilterExpr(['minVer' => '', 'maxVer' => '4.5', 'maxCount' => NULL], '@4.5');
assertFilterExpr(['minVer' => '', 'maxVer' => '4.5', 'maxCount' => '13'], '@4.5:13');
assertFilterExpr(['minVer' => '', 'maxVer' => '4.5.2', 'maxCount' => NULL], '@4.5.2');
assertFilterExpr(['minVer' => '4.5.2', 'maxVer' => NULL, 'maxCount' => NULL], '@4.5.2..');
assertFilterExpr(['minVer' => '4.6.alpha1', 'maxVer' => NULL, 'maxCount' => NULL], '@4.6.alpha1..');
assertFilterExpr(['minVer' => '4.5.2', 'maxVer' => NULL, 'maxCount' => '7'], '@4.5.2..:7');
assertFilterExpr(['minVer' => '4.3.1', 'maxVer' => '4.6', 'maxCount' => NULL], '@4.3.1..4.6');
assertFilterExpr(['minVer' => '4.3', 'maxVer' => '4.6.10', 'maxCount' => NULL], '@4.3..4.6.10');
assertFilterExpr(['minVer' => '4.3', 'maxVer' => '4.6.10', 'maxCount' => '9'], '@4.3..4.6.10:9');
assertFilterExpr(['minVer' => '', 'maxVer' => '5.0.alpha1', 'maxCount' => NULL], '@5.0.alpha1');
assertFilterExpr(['minVer' => '5.0.alpha1', 'maxVer' => '5.0.beta2', 'maxCount' => NULL], '@5.0.alpha1..5.0.beta2');
assertFilterExpr(['minVer' => '5.0.alpha1', 'maxVer' => '5.0.beta2', 'maxCount' => '19'], '@5.0.alpha1..5.0.beta2:19');

printf("\n");
printf("Pass: %d\n", $assertionCount['ok']);
printf("Fail: %d\n", $assertionCount['fail']);
exit($assertionCount['fail'] > 0 ? 1 : 0);
