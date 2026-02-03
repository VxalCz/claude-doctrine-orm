---
name: doctrine-tools
description: When working with Doctrine SchemaTool, reverse engineering, schema validation, pagination, or console commands for database operations
---

## Doctrine Tools

Doctrine ORM provides powerful tools for database schema management, validation, code generation, and debugging.

### SchemaTool

SchemaTool generates database schema from entity mappings:

```php
<?php

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManagerInterface;

class DatabaseSetupService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Create schema for all entities
        $schemaTool->createSchema($classes);
    }

    public function updateSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Update schema (safe - doesn't delete data)
        $schemaTool->updateSchema($classes, true);
    }

    public function recreateSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Drop and recreate (destructive!)
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function getSchemaSql(): array
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $classes = $this->entityManager->getMetadataFactory()->getAllMetadata();

        // Get SQL without executing (for review)
        return $schemaTool->getCreateSchemaSql($classes);
    }
}
```

### SchemaValidator

Validates entity mappings and reports issues:

```php
<?php

use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\ORM\EntityManagerInterface;

class ValidationService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function validateMapping(): array
    {
        $validator = new SchemaValidator($this->entityManager);
        $errors = $validator->validateMapping();

        // Returns array of errors by class name
        // [
        //     'App\Entity\User' => [
        //         "The field 'App\Entity\User#email' uses a non-existent type 'email'.",
        //         "The association App\Entity\User#orders refers to the owning side field..."
        //     ]
        // ]

        return $errors;
    }

    public function validatePropertyTypes(): array
    {
        $validator = new SchemaValidator($this->entityManager, true);
        $errors = $validator->validateMapping();

        // Validates that PHP property types match Doctrine types
        // e.g., warns if string property uses integer type

        return $errors;
    }

    public function validateSingleClass(string $className): array
    {
        $metadata = $this->entityManager->getClassMetadata($className);
        $validator = new SchemaValidator($this->entityManager);

        return $validator->validateClass($metadata);
    }

    public function hasMappingErrors(): bool
    {
        $validator = new SchemaValidator($this->entityManager);
        $errors = $validator->validateMapping();

        return count($errors) > 0;
    }
}
```

### Common SchemaValidator Checks

```
✓ Field type exists
✓ PHP property type matches Doctrine type
✓ Target entity exists and is valid
✓ mappedBy and inversedBy are consistent
✓ Embeddable doesn't have associations
✓ Join columns reference valid fields
✓ Discriminator maps are valid
✓ Inheritance mappings are correct
```

### Reverse Engineering (EntityGenerator)

Generate entities from existing database:

```php
<?php

use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\ORM\Mapping\Driver\DatabaseDriver;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

class ReverseEngineeringService
{
    public function generateFromDatabase(
        string $outputDirectory,
        string $namespace = 'App\Entity'
    ): void {
        $paths = [__DIR__ . '/config/xml'];
        $isDevMode = true;

        $dbParams = [
            'driver'   => 'pdo_mysql',
            'user'     => 'root',
            'password' => 'password',
            'dbname'   => 'mydb',
        ];

        $config = Setup::createXMLMetadataConfiguration($paths, $isDevMode);
        $entityManager = EntityManager::create($dbParams, $config);

        // Set up database driver for reverse engineering
        $driver = new DatabaseDriver($entityManager->getConnection()->getSchemaManager());
        $driver->setNamespace($namespace);

        $entityManager->getConfiguration()->setMetadataDriverImpl($driver);

        // Get all table metadata
        $cmf = $entityManager->getMetadataFactory();
        $cmf->setMetadataFor('App\Entity\User', null);

        $classes = $driver->getAllClassNames();

        // Generate entities
        $entityGenerator = new \Doctrine\ORM\Tools\EntityGenerator();
        $entityGenerator->setGenerateAnnotations(true);
        $entityGenerator->setGenerateStubMethods(true);
        $entityGenerator->setRegenerateEntityIfExists(true);
        $entityGenerator->setUpdateEntityIfExists(true);
        $entityGenerator->setNumSpaces(4);
        $entityGenerator->setAnnotationPrefix('ORM');

        foreach ($classes as $class) {
            $metadata = $cmf->getMetadataFor($class);
            $entityGenerator->generate([$metadata], $outputDirectory);
        }
    }
}
```

### Console Commands (Symfony)

```bash
# Schema management
php bin/console doctrine:schema:create          # Create schema
php bin/console doctrine:schema:update          # Update schema
php bin/console doctrine:schema:update --force  # Execute changes
php bin/console doctrine:schema:update --dump-sql  # Preview SQL
php bin/console doctrine:schema:drop            # Drop schema
php bin/console doctrine:schema:drop --force    # Execute drop
php bin/console doctrine:schema:validate        # Validate schema

# Entity generation
php bin/console doctrine:mapping:convert xml config/doctrine  # Convert to XML
php bin/console doctrine:mapping:convert yml config/doctrine  # Convert to YAML
php bin/console doctrine:mapping:import "App\Entity" annotation  # Import from DB

# Database info
php bin/console doctrine:database:create        # Create database
php bin/console doctrine:database:drop          # Drop database
php bin/console doctrine:query:sql "SELECT * FROM users"  # Execute SQL
php bin/console doctrine:query:dql "SELECT u FROM App\Entity\User u"  # Execute DQL

# Entity debugging
php bin/console doctrine:mapping:info           # List mapped entities
php bin/console doctrine:mapping:describe App\Entity\User  # Show mapping details
```

### Pagination

```php
<?php

use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\ORM\Query;

class PaginatedRepository
{
    public function findPaginated(
        int $page = 1,
        int $perPage = 20,
        ?string $search = null
    ): PaginatedResult {
        $qb = $this->createQueryBuilder('u')
            ->orderBy('u.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('u.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $query = $qb
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery();

        // Use Paginator for accurate count with joins
        $paginator = new Paginator($query, true);

        return new PaginatedResult(
            items: iterator_to_array($paginator),
            totalItems: count($paginator),
            currentPage: $page,
            perPage: $perPage
        );
    }
}

class PaginatedResult
{
    public function __construct(
        public array $items,
        public int $totalItems,
        public int $currentPage,
        public int $perPage
    ) {}

    public function getTotalPages(): int
    {
        return (int) ceil($this->totalItems / $this->perPage);
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->getTotalPages();
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }
}
```

### Debug Tools

```php
<?php

use Doctrine\ORM\Tools\Debug;
use Doctrine\ORM\UnitOfWork;

// Dump entity data without recursion issues
$user = $entityManager->find(User::class, 1);
$debugData = Debug::export($user, 2); // Max depth 2
print_r($debugData);

// Check Unit of Work state
$uow = $entityManager->getUnitOfWork();

// Get all scheduled changes
$insertions = $uow->getScheduledEntityInsertions();
$updates = $uow->getScheduledEntityUpdates();
$deletions = $uow->getScheduledEntityDeletions();

// Check if entity is managed
$isManaged = $uow->isInIdentityMap($user);
$isScheduled = $uow->isScheduledForInsert($user);

// Get original entity data (before changes)
$originalData = $uow->getOriginalEntityData($user);
```

### Setup Helper

```php
<?php

use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\EntityManager;

// Modern approach (Doctrine ORM 2.12+)
$config = ORMSetup::createAttributeMetadataConfiguration(
    [__DIR__ . '/src/Entity'],  // Entity paths
    true,                       // Dev mode
    __DIR__ . '/var/cache',     // Proxy cache dir
    null                        // Cache implementation
);

$entityManager = new EntityManager($connection, $config);

// For XML mapping
$config = ORMSetup::createXMLMetadataConfiguration(
    [__DIR__ . '/config/doctrine'],
    true
);

// For YAML mapping
$config = ORMSetup::createYAMLMetadataConfiguration(
    [__DIR__ . '/config/doctrine'],
    true
);

// For annotation mapping (legacy)
$config = ORMSetup::createAnnotationMetadataConfiguration(
    [__DIR__ . '/src/Entity'],
    true,
    __DIR__ . '/var/cache',
    null,
    false  // Use simple annotation reader
);
```

### Best Practices

1. **Always validate schema** - Run `doctrine:schema:validate` in CI/CD
2. **Use migrations for production** - SchemaTool is for development only
3. **Review generated SQL** - Use `--dump-sql` before `--force`
4. **Test pagination** - Verify performance with large datasets
5. **Cache metadata** - Enable caching in production
6. **Reverse engineer carefully** - Review generated entities for accuracy
7. **Keep entities in version control** - Don't regenerate in production

### Common Issues

```
❌ Schema validation errors:
   - Check mappedBy/inversedBy consistency
   - Verify target entity classes exist
   - Ensure property types match column types

❌ Update schema fails:
   - May need to use migrations for complex changes
   - Foreign keys may prevent column drops
   - Check for reserved SQL keywords

❌ Pagination slow:
   - Use Paginator with fetch joins carefully
   - Consider separate count query for large datasets
   - Add appropriate indexes

❌ Reverse engineering issues:
   - Table names may not map to valid class names
   - Foreign key names may conflict
   - Custom database types need mapping
```
