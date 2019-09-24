<?php
namespace Silver\Database\Test;

function autoload($class) {
	if (strpos($class, 'Silver') !== 0) {
		return;
	}
	$x = explode('\\', $class);
	array_shift($x);
	array_shift($x);
	$path = '../' . implode('/', $x) . '.php';
	if (file_exists($path)) {
		require_once($path);
	}
}
spl_autoload_register('Silver\\Database\\Test\\autoload');

require_once('Test.php');

Test::init();

$start = microtime(true);
Test::runOne('sqlite');
$elapsed = microtime(true) - $start;
echo "Elapsed: " . ($elapsed * 1000) . "ms";
