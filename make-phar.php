<?php

/*
 * This file is part of phpunit-phar.
 *
 * (c) Igor Wiedler <igor@wiedler.ch>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$readonly = ini_get('phar.readonly');
if ($readonly == '1' || $readonly === true) {
    die(".phar creation disabled in php.ini!\nRe-run with 'php -d phar.readonly=0 make-phar.php'\n");
} 

require __DIR__.'/symfony/src/Symfony/Component/ClassLoader/UniversalClassLoader.php';
$loader = new Symfony\Component\ClassLoader\UniversalClassLoader;
$loader->registerNamespace('Symfony', __DIR__.'/symfony/src');
$loader->register();

$pharFile = 'phpunit.phar';

if (file_exists($pharFile)) {
    unlink($pharFile);
}

// make output a little prettier
$version_file = file_get_contents(__DIR__.'/phpunit/PHPUnit/Runner/Version.php');
if (strpos($version_file, '@package_version@') !== false) {
    $pkg = simplexml_load_file(__DIR__.'/phpunit/package.xml');
    $package_version = (string) $pkg->version->release;
    $version_file = str_replace('@package_version@', $package_version, $version_file);
    file_put_contents(__DIR__.'/phpunit/PHPUnit/Runner/Version.php', $version_file);
}

$phar = new Phar($pharFile, 0, 'phpunit.phar');
$phar->setSignatureAlgorithm(Phar::SHA1);
$phar->startBuffering();

$finder = new Symfony\Component\Finder\Finder();
$finder->files()
    ->ignoreVCS(true)
    ->notName('make-phar.php')
    ->notName('phpunit.phar')
    ->notName('vendors.sh')
    ->exclude('symfony')
    ->exclude('Tests')
    ->in(__DIR__)
;

foreach ($finder as $file) {
    $filename = substr((string)$file, strlen(__DIR__.'/'));
    $phar->addFile($filename);
}

$phar['_stub.php'] = <<<EOF
<?php

set_include_path(
    __DIR__.'/phpunit'.PATH_SEPARATOR.
    __DIR__.'/dbunit'.PATH_SEPARATOR.
    __DIR__.'/php-file-iterator'.PATH_SEPARATOR.
    __DIR__.'/php-text-template'.PATH_SEPARATOR.
    __DIR__.'/php-code-coverage'.PATH_SEPARATOR.
    __DIR__.'/php-token-stream'.PATH_SEPARATOR.
    __DIR__.'/php-timer'.PATH_SEPARATOR.
    __DIR__.'/phpunit-mock-objects'.PATH_SEPARATOR.
    __DIR__.'/phpunit-selenium'.PATH_SEPARATOR.
    __DIR__.'/phpunit-story'.PATH_SEPARATOR.
    __DIR__.'/php-invoker'.PATH_SEPARATOR.
    get_include_path()
);

require 'PHPUnit/Autoload.php';

PHPUnit_TextUI_Command::main();

__HALT_COMPILER();
EOF;

$phar->setDefaultStub('_stub.php');
$phar->stopBuffering();
unset($phar);
