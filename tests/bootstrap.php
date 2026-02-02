<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

Tester\Environment::setup();
Tester\Environment::setupFunctions();

// Ensure fixtures directory exists
$fixturesDir = __DIR__ . '/fixtures';
if (!is_dir($fixturesDir)) {
	mkdir($fixturesDir, 0777, true);
}

// Ensure fixtures directory exists
$fixturesDir = __DIR__ . '/fixtures';
if (!is_dir($fixturesDir)) {
	mkdir($fixturesDir, 0777, true);
}


function runHookScript(string $scriptPath, array $input): array
{
	$process = proc_open(
		'php ' . escapeshellarg($scriptPath),
		[
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr
		],
		$pipes,
	);

	fwrite($pipes[0], json_encode($input));
	fclose($pipes[0]);

	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);

	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[2]);

	$exitCode = proc_close($process);

	return [
		'exitCode' => $exitCode,
		'stdout' => $stdout,
		'stderr' => $stderr,
	];
}


/**
 * Helper to test entity validation with automatic cleanup
 * Creates a fixture file, runs the hook, asserts results, and cleans up
 *
 * @param string $scriptPath Path to the hook script
 * @param string $fixturesDir Directory for temporary fixture files
 * @param string $fileName Temporary file name (without path)
 * @param string $phpCode PHP code to write to the fixture file
 * @param int $expectedExitCode Expected exit code (0 = success, 2 = validation errors)
 * @param string|null $expectedError Optional string to check in stderr
 * @return array The result from runHookScript
 */
function testEntityValidation(
	string $scriptPath,
	string $fixturesDir,
	string $fileName,
	string $phpCode,
	int $expectedExitCode = 2,
	?string $expectedError = null
): array {
	$entityFile = $fixturesDir . '/' . $fileName;
	file_put_contents($entityFile, $phpCode);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Tester\Assert::same($expectedExitCode, $result['exitCode']);

	if ($expectedError !== null) {
		Tester\Assert::contains($expectedError, $result['stderr']);
	}

	unlink($entityFile);

	return $result;
}
