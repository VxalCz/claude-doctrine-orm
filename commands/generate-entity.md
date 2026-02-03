---
name: generate-entity
description: Generate a new Doctrine entity with fields, mapping, and repository
allowed-tools: Bash, Read, Write, AskUserQuestion
---

# Generate Doctrine Entity

Creates a new Doctrine entity class with proper attributes, properties, getters/setters, and optional repository.

---

## Step 1: Gather Entity Information

Use AskUserQuestion to collect entity details:

1. **Entity name** (e.g., "Product", "User", "Order")
2. **Namespace** (e.g., "App\Entity", "Domain\Product")
3. **Fields** - ask for property name and type pairs
4. **Need repository?** Yes/No

---

## Step 2: Validate Input

1. **Check entity name**
   - Must be valid PHP class name (letters, numbers, underscore, starts with letter)
   - Must not already exist in target directory

2. **Check namespace**
   - Must follow PSR-4 conventions
   - Directory must exist or be creatable

---

## Step 3: Generate Entity File

Create the entity file with this structure:

```php
<?php

declare(strict_types=1);

namespace {Namespace};

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class {EntityName}
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Generated fields here

    public function getId(): ?int
    {
        return $this->id;
    }

    // Generated getters/setters here
}
```

### Field Type Mapping

| User Input | Doctrine Type | PHP Type |
|------------|---------------|----------|
| string | string | string |
| text | text | string |
| int | integer | int |
| integer | integer | int |
| bool | boolean | bool |
| boolean | boolean | bool |
| float | float | float |
| decimal | decimal | string |
| datetime | datetime | DateTime |
| datetime_immutable | datetime_immutable | DateTimeImmutable |
| date | date | DateTime |
| json | json | array |
| uuid | uuid | string |

---

## Step 4: Generate Repository (Optional)

If repository requested, create:

```php
<?php

declare(strict_types=1);

namespace {Namespace};

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<{EntityName}>
 */
class {EntityName}Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, {EntityName}::class);
    }
}
```

---

## Step 5: Verify Generation

1. **Check PHP syntax**
   ```bash
   php -l {entity-file-path}
   ```

2. **Confirm success**
   - Show generated file path
   - Show entity class name
   - If repository generated, show its path

---

## Troubleshooting

### "Class already exists"
- Entity with this name already exists in target namespace
- Choose different name or delete existing file

### "Invalid namespace"
- Namespace doesn't match PSR-4 mapping
- Check composer.json autoload configuration

### "Permission denied"
- Check write permissions to entity directory
- Run with appropriate permissions
