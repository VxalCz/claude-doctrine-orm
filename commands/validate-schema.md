---
name: validate-schema
description: Validate Doctrine schema mapping and database consistency
allowed-tools: Bash, Read, AskUserQuestion
---

# Validate Doctrine Schema

Validates that entity mappings are correct and compatible with database schema.

---

## Step 1: Pre-flight Checks

1. **Check entities exist**
   - Look for Entity files in src/Entity/ or configured paths
   - If no entities found: Inform user

2. **Check database connection**
   - Verify connection parameters are configured
   - Test connection to database

---

## Step 2: Run Schema Validation

### Option A: Using Symfony Console

```bash
php bin/console doctrine:schema:validate
```

### Option B: Using Doctrine CLI (non-Symfony)

```bash
vendor/bin/doctrine orm:validate-schema
```

### Option C: Using SchemaValidator programmatically

If console commands not available, create temporary validation script.

---

## Step 3: Interpret Results

Validation checks three aspects:

### 1. Mapping Validation
| Result | Meaning | Action |
|--------|---------|--------|
| [OK] | All entity mappings are valid | None needed |
| [FAIL] | Invalid mapping detected | Show specific errors |

Common mapping errors:
- Missing #[Id] on entity
- Invalid association mapping (mappedBy/inversedBy mismatch)
- Unknown column type
- Missing targetEntity on association

### 2. Database Synchronization
| Result | Meaning | Action |
|--------|---------|--------|
| [OK] | Database matches entities | None needed |
| [FAIL] | Schema out of sync | Run `make-migration` or `doctrine:schema:update` |

### 3. Entity Listener Check (optional)
- Verifies entity listeners are properly configured

---

## Step 4: Suggest Fixes

Based on validation results:

### For mapping errors:
- Show specific file and line
- Suggest correction
- Offer to open file for editing

### For schema out of sync:
- Offer to generate migration (`make-migration`)
- Or show SQL diff with `doctrine:schema:update --dump-sql`

---

## Additional Validation Commands

### Check specific entity
```bash
php bin/console doctrine:mapping:info --entity=App\Entity\User
```

### Show full database schema
```bash
php bin/console doctrine:schema:update --dump-sql
```

### Validate single table
```bash
php bin/console doctrine:schema:validate --filter=users
```

---

## Troubleshooting

### "No Metadata Classes to process"
- Entities not found in configured paths
- Check `doctrine.orm.mappings` configuration
- Verify entity files exist and are properly namespaced

### "The mapping file is invalid"
- XML/YAML mapping file has syntax error
- Check file against Doctrine XSD/schema

### "Unknown database type"
- Custom DBAL type not registered
- Check `doctrine.dbal.types` configuration
