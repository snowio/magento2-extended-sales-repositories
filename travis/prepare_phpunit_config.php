<?php
$magentoPath = getcwd();
if (isset($argv[1])) {
    $suggestedPath = realpath($argv[1]);
    if ($suggestedPath) {
        $magentoPath = $suggestedPath;
    }
}

if (!is_file($magentoPath . '/app/etc/di.xml')) {
    throw new \Exception('Could not detect magento root: ' . $magentoPath);
}

$configPath = "$magentoPath/dev/tests/integration/phpunit.xml.dist";
$travisBuildDir = realpath(__DIR__ . '/src/');
$packageName = \exec("composer config name -d $travisBuildDir");

$config = new \SimpleXMLElement($configPath, 0, true);

unset($config->testsuites);
$testsuiteNode = $config->addChild('testsuites')->addChild('testsuite');
$testsuiteNode->addAttribute('name', 'Integration');
$testsuiteNode->addChild('directory', "$travisBuildDir/Test/Integration")->addAttribute('suffix', 'Test.php');

$config->asXML($configPath);
