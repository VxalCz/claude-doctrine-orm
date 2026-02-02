# Claude Code Plugin Development Guide

This guide explains how to create plugins for Claude Code - from basic structure to advanced features like hooks and slash commands.

## Table of Contents

1. [Plugin Overview](#plugin-overview)
2. [Directory Structure](#directory-structure)
3. [LLM Documentation (llms.txt)](#llm-documentation-llmstxt)
4. [Plugin Boilerplate](#plugin-boilerplate)
4. [Plugin Configuration](#plugin-configuration)
5. [Skills System](#skills-system)
6. [Hooks System](#hooks-system)
7. [Tool Input Reference](#tool-input-reference)
8. [Slash Commands](#slash-commands)
9. [Testing Your Plugin](#testing-your-plugin)
10. [Local Development](#local-development)
11. [Publishing](#publishing)
12. [Best Practices](#best-practices)
13. [Comparison with Similar Tools](#comparison-with-similar-tools)
14. [User Configuration](#user-configuration)
15. [Troubleshooting & Debugging](#troubleshooting--debugging)
16. [Security Deep Dive](#security-deep-dive)
17. [Performance Best Practices](#performance-best-practices)
18. [Advanced Hook Patterns](#advanced-hook-patterns)
19. [Plugin Lifecycle](#plugin-lifecycle)
20. [Inter-Plugin Communication](#inter-plugin-communication)
21. [JSON Schemas](#json-schemas)
22. [Quick Start Guide](#quick-start-guide)
23. [Real-World Examples](#real-world-examples)
24. [FAQ](#faq)
25. [Release Checklist](#release-checklist)
26. [CI/CD Integration](#cicd-integration)
27. [Common Pitfalls](#common-pitfalls)

---

## Plugin Overview

Claude Code plugins extend the capabilities of Claude Code CLI by providing:

- **Skills**: Contextual documentation injected into conversations based on activation patterns
- **Hooks**: Scripts that run after specific tool uses (e.g., validate files after saving)
- **Commands**: Custom slash commands available to users

Plugins are loaded from local directories or the marketplace.

---

## Directory Structure

A plugin follows this structure:

```
my-plugin/
├── .claude-plugin/
│   ├── plugin.json          # Plugin metadata (required)
│   └── marketplace.json     # Marketplace publishing info (optional)
├── skills/                  # Contextual documentation (optional)
│   └── {skill-name}/
│       ├── SKILL.md         # Main skill file
│       └── {topic}.md       # Additional topic files
├── hooks/                   # PostToolUse scripts (optional)
│   ├── hooks.json          # Hook configuration
│   └── {hook-name}.php     # Hook implementation
└── commands/               # Slash commands (optional)
    └── {command-name}.md
```

---

## LLM Documentation (llms.txt)

The [llms.txt](https://llmstxt.org/) standard provides a way to document your plugin for LLMs. It helps AI assistants understand your plugin's structure, skills, and capabilities.

### What is llms.txt?

`llms.txt` is a markdown file in the project root that provides structured information about your plugin:
- Overview and purpose
- Links to all skills, commands, and hooks
- Optional/secondary resources

`llms-ctx.txt` contains the full content of all documentation (expanded skills).

### Why Use It?

1. **Standardized format** - LLMs can parse it programmatically
2. **Context for inference** - When users need help with your plugin, LLMs have complete context
3. **Discoverability** - Clear overview of all available features
4. **Future-proof** - As LLMs evolve, structured documentation becomes more valuable

### llms.txt Format

Follow the [llmstxt.org](https://llmstxt.org/) specification:

```markdown
# Plugin Name

> Brief description of what the plugin does and its key features.

## Skills

- [skill-name](path/to/skill/SKILL.md): Description of skill
- [another-skill](path/to/another/SKILL.md): Description

## Commands

- [command-name](path/to/command.md): What the command does

## Hooks

- [hook-name](path/to/hook.php): What the hook validates

## Optional

- [extra-docs](path/to/extra.md): Additional documentation
```

### Creating llms.txt

1. **Create the overview file** (`llms.txt` in project root):

```markdown
# My Plugin

> Short, focused description of the plugin's purpose and main benefits.

## Skills

Core documentation files:
- [main-skill](plugins/my-plugin/skills/main/SKILL.md): Main functionality
- [advanced](plugins/my-plugin/skills/advanced/SKILL.md): Advanced features

## Commands

Available slash commands:
- [generate](plugins/my-plugin/commands/generate.md): /generate command

## Hooks

Validation hooks:
- [validate](plugins/my-plugin/hooks/validate.php): File validation

## Optional

Extended documentation:
- [troubleshooting](docs/troubleshooting.md): Common issues
```

2. **Create full context** (`llms-ctx.txt`):

Build this by concatenating `llms.txt` with all skill contents (without YAML frontmatter):

```bash
# Copy llms.txt as base
cat llms.txt > llms-ctx.txt

# Append each skill's content (removing frontmatter)
echo -e "\n## skill-name\n" >> llms-ctx.txt
sed '/^---$/,/^---$/d' plugins/my-plugin/skills/main/SKILL.md >> llms-ctx.txt
```

3. **Reference in README**:

Add to your README.md:

```markdown
## LLM Context

This project follows the [llms.txt](https://llmstxt.org/) standard:

- **[llms.txt](llms.txt)** - Structured overview of all skills, commands, and hooks
- **[llms-ctx.txt](llms-ctx.txt)** - Full context with complete skill documentation
```

### Best Practices

1. **Keep llms.txt concise** - Overview only, not full content
2. **Use Optional section wisely** - Links that can be skipped for shorter context
3. **Maintain it** - Update when adding new skills/commands
4. **Follow the spec** - H1 title, blockquote summary, H2 sections, markdown lists
5. **Make it human-readable** - It's for LLMs, but humans read it too

### Example Structure

```
my-plugin/
├── llms.txt              # Overview (required)
├── llms-ctx.txt          # Full content (recommended)
├── README.md             # Human-focused docs
├── .claude-plugin/
│   └── plugin.json
├── skills/
│   └── ...
└── hooks/
    └── ...
```

---

## Plugin Boilerplate

Copy-paste ready template for new plugins:

```
my-plugin/
├── .claude-plugin/
│   └── plugin.json
├── skills/
│   └── main/
│       └── SKILL.md
└── hooks/
    ├── hooks.json
    └── validate.php
```

**plugin.json:**
```json
{
	"name": "my-plugin",
	"version": "1.0.0",
	"description": "Description of your plugin",
	"author": {"name": "Your Name"},
	"keywords": ["keyword1", "keyword2"],
	"skills": "./skills/",
	"hooks": "./hooks/"
}
```

**skills/main/SKILL.md:**
```markdown
---
name: main
description: When working with [specific technology/concept]
---

## Overview

Brief description of what this skill covers.

### Common Patterns

```
Example code here
```

### Best Practices

1. First practice
2. Second practice
3. Third practice
```

**hooks/hooks.json:**
```json
{
	"hooks": {
		"PostToolUse": [
			{
				"matcher": "Edit|Write",
				"hooks": [
					{
						"type": "command",
						"command": "php ${CLAUDE_PLUGIN_ROOT}/hooks/validate.php"
					}
				]
			}
		]
	}
}
```

**hooks/validate.php:**
```php
<?php

declare(strict_types=1);

$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path ?? '';

// Skip if not our file type
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'ext') {
	exit(0);
}

// Skip if file doesn't exist
if (!file_exists($filePath)) {
	exit(0);
}

// Run validation
// ... your logic here ...

exit(0);
```

---

## Plugin Configuration

### plugin.json (Required)

The `plugin.json` file defines your plugin's metadata:

```json
{
	"name": "my-plugin",
	"version": "1.0.0",
	"description": "Short description of what your plugin does",
	"author": {
		"name": "Your Name",
		"email": "you@example.com"
	},
	"keywords": ["php", "framework", "keyword1", "keyword2"],
	"skills": "./skills/",
	"hooks": "./hooks/",
	"commands": "./commands/"
}
```

**Fields:**

- `name` (required): Unique plugin identifier, lowercase with hyphens
- `version` (required): Semantic version (e.g., "1.0.0")
- `description` (required): Short summary shown in plugin listings
- `author` (optional): Object with `name` and optional `email`
- `keywords` (optional): Array of tags for searchability
- `skills` (optional): Path to skills directory relative to plugin.json
- `hooks` (optional): Path to hooks directory relative to plugin.json
- `commands` (optional): Path to commands directory relative to plugin.json

---

## Skills System

Skills provide contextual documentation that Claude includes in conversations when certain conditions are met.

### Skill Structure

Each skill is a directory containing markdown files:

```
skills/
└── my-skill/
    ├── SKILL.md          # Main skill file with YAML frontmatter
    └── advanced.md       # Optional additional files
```

### SKILL.md Format

SKILL.md must include YAML frontmatter at the top:

```markdown
---
name: my-skill
description: When to activate this skill - used for contextual matching
---

## Main Content

Your documentation here. Use markdown formatting.

```code
Example code blocks
```

### Subsections

More detailed information...
```

**Frontmatter Fields:**

- `name` (required): Unique identifier for the skill within your plugin
- `description` (required): Natural language description of when to use this skill. Claude uses this to decide when to include the skill context.

### Writing Effective Skills

1. **Be specific in descriptions**: Instead of "React help", use "When working with React components, hooks, JSX syntax, or state management"

2. **Include practical examples**: Show real code patterns users will actually use

3. **Cover common pitfalls**: Document anti-patterns and what to avoid

4. **Link between skills**: Use relative links `[text](other-skill/SKILL.md)` for cross-referencing

5. **Keep it focused**: One skill per major topic (e.g., separate skills for "React Hooks" and "React Components")

### Example: Multi-File Skill

```
skills/
└── react-hooks/
    ├── SKILL.md          # Overview and basic hooks
    ├── use-effect.md     # Detailed useEffect patterns
    └── custom-hooks.md   # Creating custom hooks
```

SKILL.md:
```markdown
---
name: react-hooks
description: When working with React hooks (useState, useEffect, useContext, custom hooks)
---

## React Hooks

Hooks allow function components to use state and lifecycle features.

### Basic Hooks

```javascript
const [count, setCount] = useState(0);
```

For useEffect details, see [useEffect patterns](use-effect.md).
```

---

## Hooks System

Hooks are scripts that execute after Claude uses certain tools (PostToolUse events). They're useful for validation, linting, or auto-fixing files.

### hooks.json Configuration

```json
{
	"description": "What these hooks do",
	"hooks": {
		"PostToolUse": [
			{
				"matcher": "Edit|Write",
				"hooks": [
					{
						"type": "command",
						"command": "php ${CLAUDE_PLUGIN_ROOT}/hooks/validate-php.php"
					}
				]
			}
		]
	}
}
```

**Configuration:**

- `matcher`: Regex pattern matching tool names (e.g., "Edit|Write" for file modifications)
- `type`: Currently only "command" is supported
- `command`: Shell command to execute. Use `${CLAUDE_PLUGIN_ROOT}` placeholder for plugin directory path.

### Hook Script Implementation

Hook scripts receive JSON input via stdin with this structure:

```json
{
	"tool_name": "Edit",
	"tool_input": {
		"file_path": "/path/to/file.php",
		"old_string": "...",
		"new_string": "..."
	},
	"tool_output": "...",
	"cwd": "/project/root"
}
```

**PHP Hook Template:**

```php
<?php

declare(strict_types=1);

/**
 * PostToolUse hook: Description of what this hook does
 */

$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path ?? '';
$cwd = $input->cwd ?? '';

// Early exit if file type doesn't match
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
	exit(0);
}

// Check if file exists (it was just written)
if (!file_exists($filePath)) {
	exit(0);
}

// Run your validation tool
$tool = 'your-linter-command';  // e.g., 'php -l', 'eslint', './vendor/bin/phpcs'
exec(escapeshellarg($tool) . ' ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

if ($exitCode === 0) {
	exit(0);  // Success
} else {
	fwrite(STDERR, "Error message\n");
	fwrite(STDERR, implode("\n", $output) . "\n");
	exit(2);  // Error - will be shown to user
}
```

**Exit Codes:**

- `0`: Success, no action needed
- `1`: General error or hook execution failure (Claude may show generic error)
- `2`: Validation error with specific message (Claude shows STDERR output to user)

Use exit code `2` when you want to display a specific error message to the user.

**Important Patterns:**

1. **Always check file extension first** - Avoid running tools on irrelevant files
2. **Handle missing files gracefully** - The file might not exist in some edge cases
3. **Use absolute paths** - Combine `$cwd` with relative paths when needed
4. **Escape shell arguments** - Always use `escapeshellarg()` to prevent injection
5. **Support Windows** - Check `PHP_OS_FAMILY` for OS-specific behavior

---

## Slash Commands

Slash commands add custom `/command-name` actions that users can invoke.

### Command Structure

Commands are markdown files in the `commands/` directory:

```
commands/
└── my-command.md
```

### Command File Format

```markdown
---
name: my-command
description: What this command does
---

# /my-command

## Purpose

Detailed explanation of what this command accomplishes.

## Usage

```
/my-command argument
```

## Implementation

When this command is invoked, Claude will:

1. Step one...
2. Step two...
3. Step three...
```

**Frontmatter Fields:**

- `name` (required): Command name without the slash (e.g., "commit" becomes `/commit`)
- `description` (required): Shown in command help and autocomplete

---

## Tool Input Reference

Hook scripts receive JSON via stdin. The structure varies by tool type:

### Edit Tool Input

```json
{
	"tool_name": "Edit",
	"tool_input": {
		"file_path": "/path/to/file.php",
		"old_string": "original content",
		"new_string": "replacement content"
	},
	"tool_output": "Ok",
	"cwd": "/project/root"
}
```

### Write Tool Input

```json
{
	"tool_name": "Write",
	"tool_input": {
		"file_path": "/path/to/file.php",
		"content": "file contents"
	},
	"tool_output": "Ok",
	"cwd": "/project/root"
}
```

**Note:** `Write` tool has `content` field, not `old_string`/`new_string`.

### Bash Tool Input

```json
{
	"tool_name": "Bash",
	"tool_input": {
		"command": "git status",
		"description": "Check git status"
	},
	"tool_output": "On branch main...",
	"cwd": "/project/root"
}
```

### Read Tool Input

```json
{
	"tool_name": "Read",
	"tool_input": {
		"file_path": "/path/to/file.php"
	},
	"tool_output": "file contents...",
	"cwd": "/project/root"
}
```

### Glob Tool Input

```json
{
	"tool_name": "Glob",
	"tool_input": {
		"pattern": "**/*.php"
	},
	"tool_output": "[\"/path/a.php\", \"/path/b.php\"]",
	"cwd": "/project/root"
}
```

### Accessing Fields in PHP

```php
$input = json_decode(file_get_contents('php://stdin'));

// Common fields
$toolName = $input->tool_name;
$filePath = $input->tool_input->file_path ?? null;
$workingDir = $input->cwd;

// Tool-specific fields
if ($toolName === 'Edit') {
    $oldContent = $input->tool_input->old_string;
    $newContent = $input->tool_input->new_string;
} elseif ($toolName === 'Write') {
    $content = $input->tool_input->content;
} elseif ($toolName === 'Bash') {
    $command = $input->tool_input->command;
}
```

---

## Testing Your Plugin

### Local Testing Setup

1. **Enable local plugin in a test project:**

```bash
cd /path/to/test-project
claude code --plugin /path/to/your-plugin
```

2. **Test skills:**
   - Start a conversation about your plugin's domain
   - Verify Claude includes relevant skill context
   - Check that documentation is accurate and helpful

3. **Test hooks:**
   - Edit or create files matching your hook patterns
   - Verify hooks run and validate correctly
   - Test error cases (invalid syntax, etc.)

4. **Test commands:**
   - Type `/your-command` in Claude Code
   - Verify it appears in autocomplete
   - Test the command functionality

### Automated Testing

For hook scripts, create automated tests. First, create a helper function:

```php
function runHookScript(string $scriptPath, string $jsonInput): array
{
	$descriptors = [
		0 => ['pipe', 'r'],  // stdin
		1 => ['pipe', 'w'],  // stdout
		2 => ['pipe', 'w'],  // stderr
	];

	$process = proc_open(['php', $scriptPath], $descriptors, $pipes);

	fwrite($pipes[0], $jsonInput);
	fclose($pipes[0]);

	$stdout = stream_get_contents($pipes[1]);
	$stderr = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	$exitCode = proc_close($process);

	return [
		'exitCode' => $exitCode,
		'stdout' => $stdout,
		'stderr' => $stderr,
	];
}
```

Then write your tests:

```php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use function Tester\assert;

// Test hook with valid file
$json = json_encode([
	'tool_name' => 'Write',
	'tool_input' => ['file_path' => __DIR__ . '/fixtures/valid.php'],
	'cwd' => __DIR__,
]);

$result = runHookScript(__DIR__ . '/../hooks/validate-php.php', $json);
assert($result['exitCode'] === 0, 'Valid file should pass');
assert($result['stderr'] === '', 'Valid file should produce no errors');

// Test hook with invalid file
$json = json_encode([
	'tool_name' => 'Write',
	'tool_input' => ['file_path' => __DIR__ . '/fixtures/invalid.php'],
	'cwd' => __DIR__,
]);

$result = runHookScript(__DIR__ . '/../hooks/validate-php.php', $json);
assert($result['exitCode'] === 2, 'Invalid file should fail');
assert(strpos($result['stderr'], 'error') !== false, 'Should show error message');

// Test hook skips non-PHP files
$json = json_encode([
	'tool_name' => 'Write',
	'tool_input' => ['file_path' => __DIR__ . '/fixtures/style.css'],
	'cwd' => __DIR__,
]);

$result = runHookScript(__DIR__ . '/../hooks/validate-php.php', $json);
assert($result['exitCode'] === 0, 'Should skip non-PHP files');
```

---

## Local Development

### Development Workflow

1. **Create plugin structure:**

```bash
mkdir -p my-plugin/.claude-plugin
mkdir -p my-plugin/skills/my-skill
mkdir -p my-plugin/hooks
touch my-plugin/.claude-plugin/plugin.json
touch my-plugin/skills/my-skill/SKILL.md
touch my-plugin/hooks/hooks.json
```

2. **Edit and iterate:**
   - Modify plugin.json
   - Add skill documentation
   - Implement hook scripts
   - Test continuously in a real project

3. **Debug hooks:**
   - Add logging to stderr: `fwrite(STDERR, "Debug: $variable\n")`
   - Run hook manually: `echo '{"tool_name":"Write",...}' | php hooks/my-hook.php`

### Common Development Issues

**Skills not appearing:**
- Check YAML frontmatter syntax (three dashes, valid YAML)
- Verify `skills` path in plugin.json is correct
- Ensure SKILL.md files exist in skill directories

**Hooks not running:**
- Check hooks.json syntax (valid JSON)
- Verify matcher pattern matches tool names (case-sensitive)
- Available tools: `Edit`, `Write`, `Read`, `Bash`, `Glob`, `Grep`, `Task`, `Skill`, etc.
- Check that script has execute permissions
- Test script manually with JSON input

**Commands not recognized:**
- Verify command file has YAML frontmatter
- Check `name` field doesn't include the slash
- Restart Claude Code after adding commands

---

## Publishing

### Marketplace Registration

Create a separate `marketplace.json` file in `.claude-plugin/` directory for publishing metadata (this is different from `plugin.json`):

```json
{
	"id": "publisher/plugin-name",
	"name": "Plugin Name",
	"description": "Short description for marketplace listing",
	"version": "1.0.0",
	"author": {
		"name": "Your Name",
		"email": "you@example.com"
	},
	"repository": "https://github.com/you/repo",
	"keywords": ["tag1", "tag2"],
	"license": "MIT"
}
```

2. **Submit to marketplace:**

```bash
/plugin marketplace add publisher/plugin-name
```

3. **Users can install:**

```bash
/plugin install publisher/plugin-name
```

### Versioning

Follow semantic versioning:

- **Major** (1.0.0 → 2.0.0): Breaking changes
- **Minor** (1.0.0 → 1.1.0): New features, backward compatible
- **Patch** (1.0.0 → 1.0.1): Bug fixes, backward compatible

Update both `plugin.json` and `marketplace.json` when releasing.

---

## Best Practices

### Plugin Design

1. **Single responsibility**: Each plugin should focus on one framework or domain
2. **Progressive enhancement**: Make features optional when possible
3. **Fail gracefully**: Hooks should exit 0 if prerequisites are missing
4. **Document assumptions**: Clearly state what your plugin expects

### Skills Writing

1. **Activation descriptions matter**: Write clear, specific descriptions for better context matching
2. **Example-driven**: Show working code, not just abstract concepts
3. **Keep it current**: Update for new versions of frameworks/tools
4. **Cross-link**: Connect related skills within your plugin

### Hook Safety

1. **Validate early**: Check file extensions before running tools
2. **Escape properly**: Always use `escapeshellarg()` and `escapeshellcmd()`
3. **Handle edge cases**: Missing files, permission errors, tool absence
4. **Don't be noisy**: Only output on actual errors

### Code Style

1. Use tabs for indentation in all file types
2. Include `declare(strict_types=1)` in PHP files
3. Write all content in English
4. Follow framework conventions you're documenting

---

## Complete Example

Here's a minimal complete plugin for a fictional "Widget Framework":

```
widget-plugin/
├── .claude-plugin/
│   └── plugin.json
├── skills/
│   └── widget-forms/
│       └── SKILL.md
└── hooks/
    ├── hooks.json
    └── lint-widget.php
```

**plugin.json:**
```json
{
	"name": "widget",
	"version": "1.0.0",
	"description": "Conventions for Widget Framework form handling",
	"author": {"name": "Developer"},
	"keywords": ["widget", "forms", "php"],
	"skills": "./skills/",
	"hooks": "./hooks/"
}
```

**skills/widget-forms/SKILL.md:**
```markdown
---
name: widget-forms
description: When creating or modifying Widget Framework forms, validation, or form components
---

## Widget Forms

Widget Framework provides a fluent form API:

```php
$form = new Form;
$form->addText('name', 'Name:')
	->setRequired();

$form->addSubmit('send', 'Submit');
```
```

**hooks/hooks.json:**
```json
{
	"hooks": {
		"PostToolUse": [
			{
				"matcher": "Edit|Write",
				"hooks": [
					{
						"type": "command",
						"command": "php ${CLAUDE_PLUGIN_ROOT}/hooks/lint-widget.php"
					}
				]
			}
		]
	}
}
```

**hooks/lint-widget.php:**
```php
<?php

declare(strict_types=1);

$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path ?? '';

if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
	exit(0);
}

// Widget-specific validation logic here
exit(0);
```

---

## Resources

- [Claude Code Documentation](https://claude.ai/code)
- [YAML Frontmatter Spec](https://yaml.org/spec/)
- [Semantic Versioning](https://semver.org/)

---

## Comparison with Similar Tools

Understanding how Claude Code plugins relate to existing tools:

| Feature | Claude Code Plugins | ESLint/Prettier | Git Hooks | Editor Extensions |
|---------|-------------------|-----------------|-----------|------------------|
| **Trigger** | Post-tool use (Edit/Write) | File system watch | Git operations | Editor events |
| **Context** | Full conversation context | File only | Staged files | Cursor position |
| **Output** | To user conversation | Console/stdout | Console | UI panels |
| **Fixes** | Can modify files directly | Auto-fix available | Can block commit | Real-time |
| **AI Aware** | Yes | No | No | No |
| **Language** | Any executable | JS/Node primarily | Any script | Varies |

### When to Use What

**Use Claude Code Plugins when:**
- You want validation integrated into AI conversation
- Context-aware help is needed
- You want to augment Claude's knowledge about specific frameworks
- File changes should trigger AI-informed validation

**Use ESLint/Prettier when:**
- You need fast, real-time feedback in IDE
- Standard code formatting is the primary goal
- You want IDE-native integration (squiggly lines, etc.)

**Use Git Hooks when:**
- You need to block commits based on validation
- Pre-push checks are required
- CI/CD pipeline integration is the priority

**Combine them:**
Plugins can complement other tools. For example, a plugin could provide deep context about framework conventions while ESLint handles syntax in the IDE.

---

## User Configuration

Allow users to configure your plugin behavior:

### Configuration File Approach

**1. Define config locations:**

```php
function findConfig(string $cwd): ?array
{
    $paths = [
        $cwd . '/.claude-plugins/my-plugin.json',
        $cwd . '/.config/my-plugin.json',
        getenv('HOME') . '/.config/claude-plugins/my-plugin.json',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            $config = json_decode(file_get_contents($path), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $config;
            }
        }
    }

    return null;
}
```

**2. Use configuration in hooks:**

```php
$config = findConfig($cwd) ?? [
    'enabled' => true,
    'strict_mode' => false,
    'exclude_patterns' => ['/vendor/', '/node_modules/'],
];

// Skip if disabled
if (!($config['enabled'] ?? true)) {
    exit(0);
}

// Check exclusion patterns
foreach ($config['exclude_patterns'] ?? [] as $pattern) {
    if (strpos($filePath, $pattern) !== false) {
        exit(0);
    }
}

// Apply strict mode
if ($config['strict_mode'] ?? false) {
    runStrictValidation($filePath);
} else {
    runStandardValidation($filePath);
}
```

**3. Document configuration options:**

```markdown
## Configuration

Create `.claude-plugins/my-plugin.json`:

```json
{
    "enabled": true,
    "strict_mode": false,
    "exclude_patterns": [
        "/tests/fixtures/",
        "/generated/"
    ]
}
```

### Options

- `enabled` (boolean): Enable/disable plugin validation
- `strict_mode` (boolean): Run additional strict checks
- `exclude_patterns` (array): Paths to exclude from validation
```

### Environment Variable Configuration

For simpler cases, use environment variables:

```php
// Check for feature flags
$strictMode = getenv('MY_PLUGIN_STRICT') === '1';
$disabled = getenv('MY_PLUGIN_DISABLED') === '1';

if ($disabled) {
    exit(0);
}
```

Users can set these in their shell profile or per-command:

```bash
MY_PLUGIN_STRICT=1 claude code
```

---

## Troubleshooting & Debugging

### Enable Verbose Logging

Add debug output to your hook scripts:

```php
// Write to stderr (won't break JSON parsing)
fwrite(STDERR, "DEBUG: Processing file: $filePath\n");
fwrite(STDERR, "DEBUG: Working directory: $cwd\n");
fwrite(STDERR, "DEBUG: Tool name: $input->tool_name\n");
```

### Test Hooks Manually

Simulate Claude Code's behavior:

```bash
# Create test JSON
cat > /tmp/test-input.json << 'EOF'
{
  "tool_name": "Write",
  "tool_input": {
    "file_path": "/path/to/test.php",
    "content": "<?php echo 'test';"
  },
  "cwd": "/project/root"
}
EOF

# Run hook manually
cat /tmp/test-input.json | php hooks/my-hook.php
echo "Exit code: $?"
```

### Common Issues and Solutions

**Hook runs but shows no output:**
- Check that output goes to STDERR, not STDOUT
- Verify exit code is 2 (not 1) for visible errors
- Test JSON output parsing doesn't fail

**Hook doesn't run at all:**
- Verify `hooks.json` is valid JSON (check with `jsonlint`)
- Check matcher pattern matches actual tool names (case-sensitive)
- Ensure script has execute permissions: `chmod +x hooks/my-hook.php`
- Test command manually with full path

**Skills don't appear in conversation:**
- Validate YAML frontmatter syntax (use online YAML validator)
- Ensure file is named exactly `SKILL.md`
- Check that `skills` path in `plugin.json` ends with `/`
- Verify description is specific enough for context matching

**Commands not recognized:**
- Command name must not include leading `/`
- File must have YAML frontmatter with `name` and `description`
- Restart Claude Code after adding new commands

### Debug Checklist

1. [ ] `plugin.json` is valid JSON and in `.claude-plugin/` directory
2. [ ] `hooks.json` is valid JSON and has correct structure
3. [ ] Hook script has `declare(strict_types=1)` and proper shebang (if executable)
4. [ ] All file paths use `${CLAUDE_PLUGIN_ROOT}` placeholder correctly
5. [ ] Matcher pattern uses correct tool names (Edit, Write, Bash, Read, etc.)
6. [ ] Skill files have proper YAML frontmatter with three dashes
7. [ ] Tested hook manually with simulated JSON input

---

## Security Deep Dive

### Input Sanitization

Never trust input from `tool_input`:

```php
// WRONG - vulnerable to injection
exec("linter $filePath");

// CORRECT - properly escaped
exec(escapeshellarg($linter) . ' ' . escapeshellarg($filePath));

// ALSO CORRECT - use array syntax (no shell interpretation)
exec([$linter, $filePath], $output, $exitCode);
```

### Path Traversal Protection

Validate file paths are within expected directories:

```php
$realPath = realpath($filePath);
$projectRoot = realpath($cwd);

if ($realPath === false || strpos($realPath, $projectRoot) !== 0) {
    fwrite(STDERR, "Error: File path outside project\n");
    exit(2);
}
```

### Restricted Operations

Hook scripts run in a sandbox with restrictions:

- Network access may be limited
- File system access is restricted to project directory
- Environment variables are sanitized
- Execution time is limited (default: 60 seconds)

### Safe Patterns for Common Operations

**Reading files:**
```php
$content = file_get_contents($filePath);
if ($content === false) {
    fwrite(STDERR, "Error: Cannot read file\n");
    exit(2);
}
```

**Writing temp files:**
```php
$tempFile = tempnam(sys_get_temp_dir(), 'my-plugin-');
file_put_contents($tempFile, $content);
// ... use temp file ...
unlink($tempFile); // Clean up
```

**Running external tools:**
```php
// Check tool exists
if (!is_executable($toolPath)) {
    exit(0); // Fail silently if tool not available
}

// Use timeout to prevent hanging
exec('timeout 30 ' . escapeshellarg($toolPath) . ' ' . escapeshellarg($filePath), $output, $exitCode);
```

### Avoid Dangerous Operations

Never do the following in hook scripts:

- Execute user-provided code (eval, exec with user input)
- Write to arbitrary file paths
- Access network resources without timeout
- Read sensitive files (.env, credentials)
- Modify system configuration

---

## Performance Best Practices

### Execution Time Limits

Hook scripts have default timeout of 60 seconds. Optimize:

```php
// Set max execution time
set_time_limit(30);

// For large projects, only check changed files
$cacheKey = md5($filePath . filemtime($filePath));
$cacheFile = sys_get_temp_dir() . '/my-plugin-' . $cacheKey;

if (file_exists($cacheFile)) {
    exit(0); // Already checked this file version
}

// Run validation
touch($cacheFile);
```

### Parallel vs Sequential Hooks

Hooks in the same array run sequentially:

```json
{
  "hooks": [
    {
      "matcher": "Edit|Write",
      "hooks": [
        {"type": "command", "command": "php hook1.php"},
        {"type": "command", "command": "php hook2.php"}
      ]
    }
  ]
}
```

If hooks are independent, combine them into one script for efficiency:

```php
// combined-hook.php
runValidation1($filePath);
runValidation2($filePath);
```

### Lazy Loading

Don't load heavy dependencies unless needed:

```php
// WRONG - always loads
require 'vendor/autoload.php';

// CORRECT - only when needed
if (pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
    require 'vendor/autoload.php';
    runPhpStan($filePath);
}
```

### Skip Unchanged Files

Check file modification time to avoid re-validation:

```php
$cacheDir = sys_get_temp_dir() . '/my-plugin-cache';
$hash = md5($filePath . filemtime($filePath) . filesize($filePath));
$cacheFile = $cacheDir . '/' . $hash;

if (file_exists($cacheFile)) {
    exit(0);
}

// Validate and cache result
$result = validateFile($filePath);
file_put_contents($cacheFile, $result ? '1' : '0');
```

### Resource Cleanup

Always clean up temporary resources:

```php
$tempDir = sys_get_temp_dir() . '/my-plugin-' . uniqid();
mkdir($tempDir);

try {
    // Do work
} finally {
    // Cleanup even on error
    array_map('unlink', glob($tempDir . '/*'));
    rmdir($tempDir);
}
```

---

## Advanced Hook Patterns

### Auto-Fix Pattern

Instead of just reporting errors, fix them automatically:

```php
$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path ?? '';

if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
    exit(0);
}

// Run fixer
exec(escapeshellarg($fixer) . ' ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);

// The fixer modifies the file directly
// Claude will detect the change and ask user to review
exit(0);
```

### Conditional Validation

Only validate certain file patterns:

```php
// Only validate files in specific directories
$allowedDirs = ['src/', 'app/', 'lib/'];
$isInAllowedDir = array_reduce($allowedDirs, function($carry, $dir) use ($filePath) {
    return $carry || strpos($filePath, $dir) !== false;
}, false);

if (!$isInAllowedDir) {
    exit(0);
}
```

### Multi-File Consistency Checks

Validate relationships between files:

```php
// Check that component has corresponding test
if (strpos($filePath, 'src/') !== false) {
    $testPath = str_replace('src/', 'tests/', $filePath);
    $testPath = preg_replace('/\.php$/', 'Test.php', $testPath);

    if (!file_exists($testPath)) {
        fwrite(STDERR, "Warning: Missing test file: $testPath\n");
        exit(2);
    }
}
```

### Staged Validation

Different validation for different file states:

```php
// Check if file is staged in git
exec('git diff --cached --name-only 2>/dev/null', $stagedFiles);

if (in_array($filePath, $stagedFiles)) {
    // Run full validation suite for staged files
    runFullValidation($filePath);
} else {
    // Quick syntax check for unstaged files
    runQuickCheck($filePath);
}
```

### Cross-Tool Validation

React differently based on tool type:

```php
if ($input->tool_name === 'Write') {
    // New file - run full validation
    runFullValidation($filePath);
} elseif ($input->tool_name === 'Edit') {
    // Edited file - check only changed parts
    $oldContent = $input->tool_input->old_string;
    $newContent = $input->tool_input->new_string;
    validateChange($oldContent, $newContent);
}
```

---

## Plugin Lifecycle

### Install Hook (Conceptual)

Currently Claude Code doesn't support install hooks, but you can document requirements:

```markdown
## Installation Requirements

This plugin requires:
- PHP 8.0+
- `phpstan` in PATH or `vendor/bin/phpstan`
- Write permissions to `.cache/` directory
```

### Version Migration

Handle configuration changes between versions:

```php
// In your hook, support legacy configs
$configFile = $cwd . '/.my-plugin.json';
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true);

    // Handle v1 config format
    if (isset($config['old_key'])) {
        $config['new_key'] = $config['old_key'];
    }
}
```

### Graceful Degradation

When dependencies are missing:

```php
$tool = findTool();

if (!$tool) {
    // Tool not available - skip validation
    fwrite(STDERR, "Note: linter not found, skipping validation\n");
    exit(0); // Exit 0 so user isn't blocked
}

// Run validation
exec(escapeshellarg($tool) . ' ' . escapeshellarg($filePath), $output, $exitCode);
```

---

## Inter-Plugin Communication

### Detecting Other Plugins

Check if another plugin is active:

```php
// Check for plugin-specific files
$hasOtherPlugin = file_exists($cwd . '/.other-plugin-config');

if ($hasOtherPlugin) {
    // Adjust behavior to avoid conflicts
    skipOverlappingValidation();
}
```

### Avoiding Conflicts

Document plugin compatibility:

```json
{
  "name": "my-plugin",
  "description": "Widget framework support (incompatible with widget-pro plugin)"
}
```

### Plugin Dependencies

Since there's no formal dependency system, check requirements at runtime:

```php
// Check for required plugin
if (!file_exists($cwd . '/.required-plugin-marker')) {
    fwrite(STDERR, "Error: This plugin requires 'required-plugin' to be installed\n");
    exit(2);
}
```

### Shared Configuration

Use standard config locations:

```php
// Look for config in standard places
$configPaths = [
    $cwd . '/.claude-plugin-config.json',
    $cwd . '/config/claude-plugin.json',
    getenv('HOME') . '/.config/claude/plugins/my-plugin.json',
];

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        $config = json_decode(file_get_contents($path), true);
        break;
    }
}
```

---

## JSON Schemas

### plugin.json Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["name", "version", "description"],
  "properties": {
    "name": {
      "type": "string",
      "pattern": "^[a-z0-9-]+$",
      "description": "Plugin identifier"
    },
    "version": {
      "type": "string",
      "pattern": "^[0-9]+\\.[0-9]+\\.[0-9]+$",
      "description": "Semantic version"
    },
    "description": {
      "type": "string",
      "maxLength": 200
    },
    "author": {
      "type": "object",
      "properties": {
        "name": { "type": "string" },
        "email": { "type": "string", "format": "email" }
      }
    },
    "keywords": {
      "type": "array",
      "items": { "type": "string" }
    },
    "skills": {
      "type": "string",
      "description": "Path to skills directory"
    },
    "hooks": {
      "type": "string",
      "description": "Path to hooks directory"
    },
    "commands": {
      "type": "string",
      "description": "Path to commands directory"
    }
  }
}
```

### hooks.json Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "properties": {
    "description": {
      "type": "string"
    },
    "hooks": {
      "type": "object",
      "properties": {
        "PostToolUse": {
          "type": "array",
          "items": {
            "type": "object",
            "required": ["matcher", "hooks"],
            "properties": {
              "matcher": {
                "type": "string",
                "description": "Regex pattern for tool names"
              },
              "hooks": {
                "type": "array",
                "items": {
                  "type": "object",
                  "required": ["type", "command"],
                  "properties": {
                    "type": {
                      "type": "string",
                      "enum": ["command"]
                    },
                    "command": {
                      "type": "string",
                      "description": "Shell command to execute"
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
  }
}
```

### marketplace.json Schema

```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["id", "name", "description", "version"],
  "properties": {
    "id": {
      "type": "string",
      "pattern": "^[a-z0-9-]+/[a-z0-9-]+$"
    },
    "name": {
      "type": "string"
    },
    "description": {
      "type": "string"
    },
    "version": {
      "type": "string"
    },
    "author": {
      "type": "object",
      "properties": {
        "name": { "type": "string" },
        "email": { "type": "string" }
      }
    },
    "repository": {
      "type": "string",
      "format": "uri"
    },
    "keywords": {
      "type": "array",
      "items": { "type": "string" }
    },
    "license": {
      "type": "string"
    }
  }
}
```

---

## Quick Start Guide

Create a working plugin in 5 minutes:

```bash
# 1. Create directory structure
mkdir -p my-plugin/.claude-plugin
mkdir -p my-plugin/skills/quick-start

# 2. Create plugin.json
cat > my-plugin/.claude-plugin/plugin.json << 'EOF'
{
	"name": "quick-start",
	"version": "1.0.0",
	"description": "My first Claude Code plugin",
	"author": {"name": "Developer"},
	"skills": "./skills/"
}
EOF

# 3. Create a skill
cat > my-plugin/skills/quick-start/SKILL.md << 'EOF'
---
name: quick-start
description: When working with example code or testing plugins
---

## Quick Start

This is my first skill! It activates when you talk about example code.

```python
# Example function
def hello():
    return "Hello from my plugin!"
```
EOF

# 4. Test it
claude code --plugin ./my-plugin
```

Once loaded, try asking Claude: "How do I write example code?" and the skill context should appear.

---

## Real-World Examples

Study existing plugins to learn best practices:

### Nette Plugin (This Repository)

Location: `plugins/nette/`

**What it demonstrates:**
- Multiple skills covering different aspects (Latte, NEON, Forms, Database)
- Multi-file skills with cross-linking
- PHP hooks for file validation
- Proper YAML frontmatter descriptions

**Key files to study:**
- `skills/latte-templates/SKILL.md` - Complex skill with examples
- `hooks/lint-latte.php` - Hook with conditional execution
- `hooks/hooks.json` - Hook configuration

### PHP Fixer Plugin

Location: `plugins/php-fixer/`

**What it demonstrates:**
- Single-purpose plugin doing one thing well
- Hook that auto-fixes PHP style issues
- Command for installing dependencies

### Nette Dev Plugin

Location: `plugins/nette-dev/`

**What it demonstrates:**
- Skills for contributors (coding standards, commit messages)
- Targeted at framework developers, not end users
- Narrow, specific activation descriptions

---

## FAQ

### General Questions

**Q: Can I use languages other than PHP for hooks?**
A: Yes, any executable script works. Python, Node.js, Ruby, bash - anything that can read stdin and write to stderr. PHP is just convenient for Nette ecosystem.

**Q: How do I debug why my skill isn't activating?**
A: Skills activate based on description matching. Try:
1. Make description more specific
2. Include keywords from your description in your question to Claude
3. Check YAML syntax is valid
4. Verify SKILL.md is in correct location

**Q: Can hooks modify the file being edited?**
A: Yes, hooks can modify files directly (auto-fix pattern). Claude will detect the change and show it to the user.

**Q: What's the difference between exit code 1 and 2?**
A: Exit 1 = generic error (Claude may show brief message). Exit 2 = specific error (Claude shows full stderr output). Use exit 2 for user-visible validation errors.

**Q: Can I have multiple plugins active?**
A: Yes, multiple plugins can be loaded simultaneously. Be careful about conflicting hooks.

### Technical Questions

**Q: How do I access environment variables?**
A: Use `getenv()` or `$_ENV`, but be aware that Claude Code may sanitize the environment for security.

```php
$home = getenv('HOME');
$path = getenv('PATH');
```

**Q: Can hooks run asynchronously?**
A: No, hooks run synchronously and block until completion. Use `timeout` command for long-running operations.

**Q: How do I test with specific Claude Code versions?**
A: Update Claude Code to desired version and test. There's no version constraint system for plugins yet.

**Q: Can I bundle dependencies with my plugin?**
A: Yes, include a `vendor/` directory or use a PHAR archive. Just reference the correct path in your hooks.

```php
$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorPath)) {
    require $vendorPath;
}
```

**Q: How do I handle Windows vs Linux differences?**
A: Check `PHP_OS_FAMILY` constant:

```php
if (PHP_OS_FAMILY === 'Windows') {
    $command = 'tool.exe';
} else {
    $command = 'tool';
}
```

### Publishing Questions

**Q: How long does marketplace approval take?**
A: Currently, there's no formal approval process. Plugins are added to the registry after submission.

**Q: Can I charge for my plugin?**
A: Currently, all marketplace plugins are free and open source.

**Q: How do I update my plugin?**
A: Update version in both `plugin.json` and `marketplace.json`, then notify users to update via `/plugin update`.

**Q: Can I have private plugins?**
A: Yes, use local plugin loading with `--plugin` flag. Private marketplace plugins aren't supported yet.

---

## Release Checklist

Before publishing a new version, verify:

### Pre-Release

- [ ] Version bumped in `plugin.json`
- [ ] Version bumped in `marketplace.json` (if exists)
- [ ] CHANGELOG.md updated with changes
- [ ] All skills have valid YAML frontmatter
- [ ] All skill descriptions are specific and accurate
- [ ] Hook scripts tested manually
- [ ] Hooks handle edge cases (missing files, errors)
- [ ] No hardcoded paths in hooks
- [ ] Security: All user inputs escaped
- [ ] Documentation proofread for typos

### Testing

- [ ] Plugin loads without errors: `claude code --plugin ./my-plugin`
- [ ] Skills activate in relevant conversations
- [ ] Hooks trigger on correct file types
- [ ] Hooks skip irrelevant file types
- [ ] Error messages are helpful and clear
- [ ] Commands appear in autocomplete
- [ ] Commands execute correctly

### Compatibility

- [ ] Tested on Windows (if applicable)
- [ ] Tested on macOS/Linux
- [ ] Tested with latest Claude Code version
- [ ] No conflicts with common other plugins

### Post-Release

- [ ] Tag release in git: `git tag v1.0.1`
- [ ] Push tags: `git push --tags`
- [ ] Announce in relevant communities
- [ ] Update marketplace listing (if applicable)

---

## CI/CD Integration

### GitHub Actions for Plugin Testing

```yaml
name: Test Plugin

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'

      - name: Validate plugin.json
        run: |
          php -r "json_decode(file_get_contents('.claude-plugin/plugin.json'));
          echo json_last_error() === JSON_ERROR_NONE ? 'Valid JSON' : 'Invalid JSON';"

      - name: Validate hooks.json
        run: |
          if [ -f hooks/hooks.json ]; then
            php -r "json_decode(file_get_contents('hooks/hooks.json'));
            echo json_last_error() === JSON_ERROR_NONE ? 'Valid JSON' : 'Invalid JSON';"
          fi

      - name: Test hook scripts
        run: |
          composer install
          vendor/bin/tester tests/ -s

      - name: Check code style
        run: |
          if [ -f vendor/bin/phpcs ]; then
            vendor/bin/phpcs hooks/
          fi
```

### Testing Against Multiple PHP Versions

```yaml
strategy:
  matrix:
    php-version: ['8.0', '8.1', '8.2', '8.3']

steps:
  - uses: actions/checkout@v3

  - name: Setup PHP
    uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php-version }}
```

### Automated Release

```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Create Release
        uses: actions/create-release@v1
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
        with:
          tag_name: ${{ github.ref }}
          release_name: Release ${{ github.ref }}
          draft: false
          prerelease: false
```

---

## Common Pitfalls

### YAML Frontmatter Issues

**Wrong:**
```markdown
---
name: my-skill
---
```
(Missing description)

**Wrong:**
```markdown
---
name: my skill
description: help with things
---
```
(Space in name, vague description)

**Right:**
```markdown
---
name: my-skill
description: When working with React form validation, useState hooks, and submit handlers
---
```

### Hook Pattern Mistakes

**Wrong - Missing extension check:**
```php
$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path;
exec("linter $filePath");  // Runs on EVERY file edit!
```

**Right:**
```php
$input = json_decode(file_get_contents('php://stdin'));
$filePath = $input->tool_input->file_path ?? '';

if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
    exit(0);
}
exec(escapeshellarg($linter) . ' ' . escapeshellarg($filePath));
```

### Path Handling Errors

**Wrong:**
```php
$cacheFile = '/tmp/my-cache';  // Hardcoded, doesn't work on Windows
```

**Right:**
```php
$cacheFile = sys_get_temp_dir() . '/my-plugin-cache-' . uniqid();
```

### JSON Input Assumptions

**Wrong:**
```php
$filePath = $input->tool_input->file_path;  // Crashes if field missing
```

**Right:**
```php
$filePath = $input->tool_input->file_path ?? '';
if (empty($filePath)) {
    exit(0);
}
```

### Exit Code Confusion

**Wrong:**
```php
if ($hasErrors) {
    echo "Errors found\n";
    exit(1);  // User won't see the message!
}
```

**Right:**
```php
if ($hasErrors) {
    fwrite(STDERR, "Errors found:\n");
    fwrite(STDERR, implode("\n", $errors) . "\n");
    exit(2);  // Shows stderr to user
}
```

### Skill Description Anti-Patterns

**Vague (bad matching):**
```yaml
description: Help with PHP
```

**Too broad:**
```yaml
description: When writing any code
```

**Too narrow:**
```yaml
description: When pressing Ctrl+S on a Tuesday
```

**Good:**
```yaml
description: When working with Laravel Eloquent models, relationships, queries, or database migrations
```

### Performance Mistakes

**Wrong - No timeout:**
```php
exec('very-slow-linter ' . escapeshellarg($filePath), $output, $exitCode);
```

**Right:**
```php
exec('timeout 30 very-slow-linter ' . escapeshellarg($filePath), $output, $exitCode);
if ($exitCode === 124) {
    fwrite(STDERR, "Warning: Linter timed out after 30 seconds\n");
    exit(0);  // Don't block user on timeout
}
```

**Wrong - Validating unchanged files:**
```php
// Runs on every edit of every file
exec('full-project-analysis ' . escapeshellarg($filePath));
```

**Right:**
```php
// Check if file changed since last validation
$cacheKey = md5($filePath . filemtime($filePath));
if (file_exists("/tmp/$cacheKey")) {
    exit(0);
}
touch("/tmp/$cacheKey");
exec('quick-syntax-check ' . escapeshellarg($filePath));
```

---

## Questions and Support

- Report issues: https://github.com/anthropics/claude-code/issues
- Plugin discussions: Check the Claude Code community forums
