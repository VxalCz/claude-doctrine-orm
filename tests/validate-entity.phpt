<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$scriptPath = __DIR__ . '/../hooks/validate-entity.php';
$fixturesDir = __DIR__ . '/fixtures';


test('skips non-php files', function () use ($scriptPath, $fixturesDir) {
	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $fixturesDir . '/test.txt'],
		'cwd' => $fixturesDir,
	]);

	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
});


test('skips when file does not exist', function () use ($scriptPath, $fixturesDir) {
	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $fixturesDir . '/nonexistent.php'],
		'cwd' => $fixturesDir,
	]);

	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);
});


test('skips non-entity php files', function () use ($scriptPath, $fixturesDir) {
	$phpFile = $fixturesDir . '/regular-class.php';
	file_put_contents($phpFile, '<?php class RegularClass {}');

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $phpFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(0, $result['exitCode']);
	Assert::same('', $result['stderr']);

	unlink($phpFile);
});


test('validates entity without id', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/no-id-entity.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class NoIdEntity
{
	#[ORM\Column]
	private string $name;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Missing #[Id]', $result['stderr']);

	unlink($entityFile);
});


test('accepts valid entity', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/valid-entity.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class ValidEntity
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private int $id;

	#[ORM\Column(length: 255)]
	private string $name;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});


test('detects string column without length', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/string-no-length.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class StringNoLength
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column(type: 'string')]
	private string $name;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('String column without length', $result['stderr']);

	unlink($entityFile);
});


test('detects php syntax error', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/syntax-error.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class SyntaxError
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id
	// Missing semicolon
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('PHP syntax error', $result['stderr']);

	unlink($entityFile);
});


test('handles empty input gracefully', function () use ($scriptPath) {
	$result = runHookScript($scriptPath, []);

	Assert::same(0, $result['exitCode']);
});


test('handles missing tool_input gracefully', function () use ($scriptPath) {
	$result = runHookScript($scriptPath, [
		'cwd' => '/tmp',
	]);

	Assert::same(0, $result['exitCode']);
});
