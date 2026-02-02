# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a **Claude Code plugin** for Doctrine ORM. It provides contextual documentation (skills) and file validation (hooks) to assist developers working with Doctrine ORM entities, queries, relationships, and migrations.

## Repository Structure

```
plugins/doctrine/
├── .claude-plugin/
│   └── plugin.json          # Plugin metadata (name, version, paths)
├── skills/                  # Contextual documentation (7 skill topics)
│   ├── doctrine-orm/        # Entity definitions, column types, basic ORM
│   ├── doctrine-query/      # DQL, QueryBuilder, repositories
│   ├── doctrine-relationships/  # Associations, joins, mappings
│   ├── doctrine-mapping/    # Advanced mapping configurations
│   ├── doctrine-lifecycle/  # Lifecycle callbacks, events
│   ├── doctrine-migrations/ # Database migrations
│   └── doctrine-tools/      # Schema tool, validators
└── hooks/
    ├── hooks.json           # Hook configuration (PostToolUse)
    └── validate-entity.php  # Entity validation hook script
```

## Development Commands

### Testing the Plugin

Load the plugin locally in a test project:

```bash
cd /path/to/test-project
claude code --plugin /path/to/claude-doctrine-orm/plugins/doctrine
```

### Testing Hooks Manually

Simulate Claude Code's hook invocation:

```bash
# Create test JSON input
cat > /tmp/test-input.json << 'EOF'
{
  "tool_name": "Write",
  "tool_input": {
    "file_path": "/path/to/test/Entity/User.php",
    "content": "<?php ..."
  },
  "cwd": "/project/root"
}
EOF

# Run hook manually
cat /tmp/test-input.json | php plugins/doctrine/hooks/validate-entity.php
echo "Exit code: $?"
```

### PHP Syntax Check

```bash
php -l plugins/doctrine/hooks/validate-entity.php
```

## Architecture

### Plugin System

Plugins extend Claude Code through three mechanisms:

1. **Skills**: Markdown documentation in `skills/{name}/SKILL.md` with YAML frontmatter (`name`, `description`). Claude includes relevant skills contextually based on the `description` field matching conversation topics.

2. **Hooks**: Scripts triggered after tool use (Edit, Write). Configured in `hooks/hooks.json` with regex matchers. Scripts receive JSON via stdin with `tool_name`, `tool_input`, `cwd`, etc.

3. **Commands**: Slash commands (not used in this plugin).

### Hook Implementation

The `validate-entity.php` hook:
- Receives JSON input via stdin containing the file path being edited
- Early-exits (code 0) for non-PHP files, non-existent files, or non-entity files
- Validates Doctrine entities for:
  - PHP syntax errors
  - Missing primary keys (`#[Id]`)
  - Invalid `#[GeneratedValue]` usage
  - String columns without length
  - Unknown column types
  - Association mapping errors (mappedBy/inversedBy mismatches)
  - PHP type/Doctrine type incompatibilities
- Exits with code 2 and writes to STDERR to display errors to users

### Skill Format

Each `SKILL.md` must have YAML frontmatter:

```yaml
---
name: doctrine-orm
description: When working with Doctrine ORM entities, repositories, entity manager...
---
```

The `description` is critical - Claude uses it to decide when to include the skill context. Be specific and include relevant keywords.

## Adding New Skills

1. Create directory: `skills/{skill-name}/`
2. Create `SKILL.md` with YAML frontmatter
3. Include practical code examples with PHP 8 attributes
4. Add to `PLUGIN_DEVELOPMENT_GUIDE.md` if it represents a major topic

## Code Style

- Use tabs for indentation (all file types)
- Include `declare(strict_types=1)` in PHP files
- Write content in English
- Follow the patterns in PLUGIN_DEVELOPMENT_GUIDE.md

## Git Conventions

- **DO NOT** include `Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>` in commit messages
- Write clear, concise commit messages that describe the changes

## Important Files

- `PLUGIN_DEVELOPMENT_GUIDE.md` - Comprehensive plugin development documentation
- `plugins/doctrine/.claude-plugin/plugin.json` - Plugin configuration
- `plugins/doctrine/hooks/hooks.json` - Hook triggers configuration
