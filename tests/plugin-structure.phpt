<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$pluginDir = __DIR__ . '/../plugins/doctrine';


test('plugin.json exists and is valid', function () use ($pluginDir) {
	$pluginJsonPath = $pluginDir . '/.claude-plugin/plugin.json';
	Assert::true(file_exists($pluginJsonPath), 'plugin.json should exist');

	$content = file_get_contents($pluginJsonPath);
	$json = json_decode($content, true);
	Assert::same(JSON_ERROR_NONE, json_last_error(), 'plugin.json should be valid JSON');

	// Required fields
	Assert::true(isset($json['name']), 'plugin.json should have name');
	Assert::true(isset($json['version']), 'plugin.json should have version');
	Assert::true(isset($json['description']), 'plugin.json should have description');
});


test('hooks.json exists and is valid', function () use ($pluginDir) {
	$hooksJsonPath = $pluginDir . '/hooks/hooks.json';
	Assert::true(file_exists($hooksJsonPath), 'hooks.json should exist');

	$content = file_get_contents($hooksJsonPath);
	$json = json_decode($content, true);
	Assert::same(JSON_ERROR_NONE, json_last_error(), 'hooks.json should be valid JSON');

	// Check structure
	Assert::true(isset($json['hooks']), 'hooks.json should have hooks key');
	Assert::true(isset($json['hooks']['PostToolUse']), 'hooks.json should have PostToolUse');
	Assert::type('array', $json['hooks']['PostToolUse']);
});


test('validate-entity.php hook exists and has valid syntax', function () use ($pluginDir) {
	$hookPath = $pluginDir . '/hooks/validate-entity.php';
	Assert::true(file_exists($hookPath), 'validate-entity.php should exist');

	// Check PHP syntax
	exec('php -l ' . escapeshellarg($hookPath) . ' 2>&1', $output, $exitCode);
	Assert::same(0, $exitCode, 'validate-entity.php should have valid PHP syntax: ' . implode("\n", $output));
});


test('all skills have valid YAML frontmatter', function () use ($pluginDir) {
	$skillsDir = $pluginDir . '/skills';
	Assert::true(is_dir($skillsDir), 'skills directory should exist');

	$skillDirs = glob($skillsDir . '/*', GLOB_ONLYDIR);
	Assert::true(count($skillDirs) > 0, 'should have at least one skill');

	foreach ($skillDirs as $skillDir) {
		$skillMdPath = $skillDir . '/SKILL.md';
		Assert::true(file_exists($skillMdPath), basename($skillDir) . ' should have SKILL.md');

		$content = file_get_contents($skillMdPath);

		// Check for YAML frontmatter
		Assert::match('~^---\n.*\n---\n~s', $content, basename($skillDir) . '/SKILL.md should have YAML frontmatter');

		// Extract and parse frontmatter
		if (preg_match('~^---\n(.*?)\n---\n~s', $content, $matches)) {
			$yaml = $matches[1];
			Assert::contains('name:', $yaml, basename($skillDir) . ' should have name in frontmatter');
			Assert::contains('description:', $yaml, basename($skillDir) . ' should have description in frontmatter');
		}
	}
});


test('all commands have valid YAML frontmatter', function () use ($pluginDir) {
	$commandsDir = $pluginDir . '/commands';

	if (!is_dir($commandsDir)) {
		// Commands are optional
		return;
	}

	$commandFiles = glob($commandsDir . '/*.md');

	foreach ($commandFiles as $commandFile) {
		$content = file_get_contents($commandFile);

		// Check for YAML frontmatter
		Assert::match('~^---\n.*\n---\n~s', $content, basename($commandFile) . ' should have YAML frontmatter');

		// Extract and parse frontmatter
		if (preg_match('~^---\n(.*?)\n---\n~s', $content, $matches)) {
			$yaml = $matches[1];
			Assert::contains('name:', $yaml, basename($commandFile) . ' should have name in frontmatter');
			Assert::contains('description:', $yaml, basename($commandFile) . ' should have description in frontmatter');
		}
	}
});


test('llms.txt exists and has valid structure', function () {
	$llmsTxtPath = __DIR__ . '/../llms.txt';
	Assert::true(file_exists($llmsTxtPath), 'llms.txt should exist');

	$content = file_get_contents($llmsTxtPath);

	// Check for H1 title
	Assert::match('~^#\s+.+~m', $content, 'llms.txt should have H1 title');

	// Check for blockquote (description)
	Assert::match('~^\>.+~m', $content, 'llms.txt should have blockquote description');

	// Check for sections
	Assert::contains('## Skills', $content, 'llms.txt should have Skills section');
});


test('llms-ctx.txt exists and contains skill content', function () {
	$llmsCtxPath = __DIR__ . '/../llms-ctx.txt';
	Assert::true(file_exists($llmsCtxPath), 'llms-ctx.txt should exist');

	$content = file_get_contents($llmsCtxPath);

	// Should contain llms.txt content
	Assert::match('~^#\s+.+~m', $content, 'llms-ctx.txt should have H1 title from llms.txt');

	// Should contain full skill sections (without frontmatter dashes at start)
	Assert::contains('## doctrine-orm', $content, 'llms-ctx.txt should contain doctrine-orm section');
	Assert::contains('## doctrine-query', $content, 'llms-ctx.txt should contain doctrine-query section');
	Assert::contains('Entity Structure', $content, 'llms-ctx.txt should contain skill content');
});


test('required skill files exist', function () use ($pluginDir) {
	$requiredSkills = [
		'doctrine-orm',
		'doctrine-query',
		'doctrine-relationships',
		'doctrine-mapping',
		'doctrine-lifecycle',
		'doctrine-migrations',
		'doctrine-tools',
	];

	foreach ($requiredSkills as $skillName) {
		$skillPath = $pluginDir . '/skills/' . $skillName . '/SKILL.md';
		Assert::true(file_exists($skillPath), "Required skill {$skillName} should exist");
	}
});
