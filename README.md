# Doctrine ORM Plugin for Claude Code

Plugin for [Claude Code](https://claude.com/product/claude-code) – the AI-powered coding assistant by Anthropic. This plugin gives Claude deep knowledge of Doctrine ORM, including entity definitions, DQL queries, associations, lifecycle callbacks, migrations, and automatic entity validation.

## Installation

Load the plugin locally in your project:

```bash
claude code --plugin /path/to/claude-doctrine-orm/plugins/doctrine
```

## Skills

| Skill | Description |
|-------|-------------|
| **doctrine-orm** | Entity definitions, column types, basic ORM patterns |
| **doctrine-query** | DQL, QueryBuilder, repositories, query optimization |
| **doctrine-relationships** | Associations, joins, bidirectional mappings |
| **doctrine-mapping** | Advanced mapping configurations, inheritance |
| **doctrine-lifecycle** | Lifecycle callbacks, events, entity listeners |
| **doctrine-migrations** | Database migrations, version control |
| **doctrine-tools** | Schema tool, validators, debugging |

## Features

- **Automatic entity validation** – Hooks validate PHP syntax, missing primary keys, invalid column types, and association mapping errors after each file edit
- **Contextual skills activation** – Skills automatically activate based on conversation topics
- **Best practices** – Follows Doctrine ORM conventions and patterns

## Usage

Skills are automatically activated based on conversation context. For example:

- Ask about "entity mapping" → activates `doctrine-orm`
- Ask about "DQL queries" → activates `doctrine-query`
- Ask about "entity relationships" → activates `doctrine-relationships`
- Ask about "database migrations" → activates `doctrine-migrations`
- Etc..

## LLM Context Files

This project includes `llms.txt` context files for LLM consumption:

- [`llms.txt`](llms.txt) - Structured overview of all skills, commands, and documentation
- [`llms-ctx.txt`](llms-ctx.txt) - Full context with all skill contents included

These files follow the [llms.txt standard](https://llmstxt.org/) for providing LLM-friendly documentation.

## LLM Context

This project follows the [llms.txt](https://llmstxt.org/) standard for providing LLM-friendly documentation:

- **[llms.txt](llms.txt)** - Structured overview of all skills, commands, and hooks
- **[llms-ctx.txt](llms-ctx.txt)** - Full context with all skill documentation content

Use these files when you need comprehensive Doctrine ORM context for LLM inference.

## Development

See `CLAUDE.md` and `PLUGIN_DEVELOPMENT_GUIDE.md` for detailed plugin development documentation.

## Testing Hooks

Simulate Claude Code's hook invocation manually:

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
