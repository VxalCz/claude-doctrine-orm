---
name: make-migration
description: Generate a Doctrine migration based on entity changes or schema diff
allowed-tools: Bash, Read, AskUserQuestion
---

# Make Doctrine Migration

Generates a new database migration comparing current entity mappings to database schema.

---

## Step 1: Pre-flight Checks

1. **Check Doctrine Migrations is installed**
   ```bash
   composer show doctrine/migrations 2>/dev/null || echo "Not installed"
   ```
   - If not installed: Ask user to install with `composer require doctrine/doctrine-migrations-bundle`

2. **Check migrations directory exists**
   - Look for `migrations/`, `src/Migrations/`, or configured path
   - If not found: Ask user to specify migrations directory

3. **Check database connection**
   - Verify DATABASE_URL or connection params are configured
   - Test connection with `php bin/console doctrine:database:create --if-not-exists` (Symfony)
     or appropriate command for the framework

---

## Step 2: Generate Migration

1. **Run diff command**
   ```bash
   php bin/console doctrine:migrations:diff
   ```
   - For non-Symfony projects:
   ```bash
   vendor/bin/doctrine-migrations diff
   ```

2. **Check output**
   - Success: Migration file generated at path shown in output
   - No changes: Inform user no migration needed
   - Error: Show error message and troubleshooting steps

---

## Step 3: Review Generated Migration

Read the generated migration file and show summary:

- Migration version (timestamp)
- Up migration: SQL statements to execute
- Down migration: SQL statements for rollback
- Number of tables affected

---

## Step 4: Optional - Execute Migration

Ask user if they want to run the migration immediately:

```bash
php bin/console doctrine:migrations:migrate
```

---

## Troubleshooting

### "Migrations not configured"
- Check `config/packages/doctrine_migrations.yaml` (Symfony)
- Verify `migrations_paths` points to correct directory

### "No changes detected"
- Entities may already match database schema
- Check if entities are in scanned directories
- Verify metadata cache is cleared

### "Table already exists" error
- Database has tables not tracked by migrations
- Consider `doctrine:schema:drop` and recreate, or manual fix

### "Connection refused"
- Check DATABASE_URL environment variable
- Verify database server is running
- Check credentials in configuration
