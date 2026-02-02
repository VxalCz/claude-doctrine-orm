---
name: doctrine-migrations
description: When working with Doctrine Migrations, database schema versioning, migration generation, or schema updates
---

## Doctrine Migrations

Doctrine Migrations provides a structured way to version and deploy database schema changes alongside your application code.

### Installation & Configuration

```bash
composer require doctrine/migrations
```

**Configuration (migrations.php or migrations.yaml):**

```php
<?php

declare(strict_types=1);

return [
    'table_storage' => [
        'table_name' => 'doctrine_migration_versions',
        'version_column_name' => 'version',
        'version_column_length' => 191,
        'executed_at_column_name' => 'executed_at',
        'execution_time_column_name' => 'execution_time',
    ],

    'migrations_paths' => [
        'App\Migrations' => 'migrations',
    ],

    'all_or_nothing' => true,
    'transactional' => true,
    'check_database_platform' => true,
    'organize_migrations' => 'none', // or 'year', 'year_and_month'
    'connection' => null,
    'em' => null,
];
```

### CLI Commands

```bash
# View status
vendor/bin/doctrine-migrations status

# Generate migration from entity changes
vendor/bin/doctrine-migrations diff

# Create empty migration
vendor/bin/doctrine-migrations generate

# Execute migrations
vendor/bin/doctrine-migrations migrate

# Execute specific version
vendor/bin/doctrine-migrations execute --up 'App\Migrations\Version20240115120000'

# Rollback (down migration)
vendor/bin/doctrine-migrations execute --down 'App\Migrations\Version20240115120000'

# View migration versions
vendor/bin/doctrine-migrations list

# Rollback all migrations (first version)
vendor/bin/doctrine-migrations first

# Check if migrations are synced
vendor/bin/doctrine-migrations up-to-date
```

### Migration Class Structure

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\SchemaException;

final class Version20240115120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add product reviews table with foreign key to products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE product_reviews (
                id INT AUTO_INCREMENT NOT NULL,
                product_id INT NOT NULL,
                author_name VARCHAR(255) NOT NULL,
                rating SMALLINT NOT NULL,
                comment TEXT DEFAULT NULL,
                created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                INDEX IDX_REVIEWS_PRODUCT (product_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE product_reviews
            ADD CONSTRAINT FK_REVIEWS_PRODUCT
            FOREIGN KEY (product_id)
            REFERENCES products (id)
            ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_reviews DROP FOREIGN KEY FK_REVIEWS_PRODUCT');
        $this->addSql('DROP TABLE product_reviews');
    }
}
```

### Schema API (Programmatic)

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115130000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Create table using Schema API
        $table = $schema->createTable('categories');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('name', Types::STRING, ['length' => 255]);
        $table->addColumn('slug', Types::STRING, ['length' => 255]);
        $table->addColumn('description', Types::TEXT, ['notnull' => false]);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['slug'], 'UNIQ_CATEGORY_SLUG');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('categories');
    }
}
```

### Data Migrations

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115140000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // Migrate data from old column to new structure
        $this->addSql('
            INSERT INTO categories (name, slug, created_at)
            SELECT DISTINCT
                old_category as name,
                LOWER(REPLACE(old_category, " ", "-")) as slug,
                NOW() as created_at
            FROM products
            WHERE old_category IS NOT NULL
        ');

        // Update products with new category_id
        $this->addSql('
            UPDATE products p
            JOIN categories c ON p.old_category = c.name
            SET p.category_id = c.id
        ');

        // Drop old column
        $this->addSql('ALTER TABLE products DROP COLUMN old_category');
    }

    public function down(Schema $schema): void
    {
        // Reverse data migration
        $this->addSql('
            ALTER TABLE products ADD old_category VARCHAR(255) DEFAULT NULL
        ');

        $this->addSql('
            UPDATE products p
            JOIN categories c ON p.category_id = c.id
            SET p.old_category = c.name
        ');
    }
}
```

### Conditional Migrations (Database Platform)

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115150000 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof MySQLPlatform) {
            $this->addSql('
                ALTER TABLE products
                ADD FULLTEXT INDEX IDX_SEARCH (name, description)
            ');
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $this->addSql('
                CREATE INDEX IDX_SEARCH ON products
                USING gin(to_tsvector(\'english\', name || \' \' || COALESCE(description, \'\')))
            ');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_SEARCH ON products');
    }
}
```

### Migration Dependencies

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115160000 extends AbstractMigration
{
    // This migration requires another to be executed first
    public function getDependencies(): array
    {
        return [
            Version20240115120000::class,
        ];
    }

    public function up(Schema $schema): void
    {
        // Can rely on tables from Version20240115120000 existing
    }

    public function down(Schema $schema): void
    {
    }
}
```

### Skipping If Already Applied

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Query\Query;

final class Version20240115170000 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        // Skip if column already exists
        if ($schema->getTable('products')->hasColumn('new_column')) {
            $this->skipIf(true, 'Column new_column already exists');
        }
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD new_column VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP COLUMN new_column');
    }
}
```

### Migration with Warnings

```php
<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240115180000 extends AbstractMigration
{
    public function preUp(Schema $schema): void
    {
        // Warn about destructive migration
        $this->warnIf(true, 'This migration will delete orphaned records. Backup recommended.');
    }

    public function up(Schema $schema): void
    {
        // Clean up orphaned data before adding foreign key
        $this->addSql('
            DELETE FROM product_reviews
            WHERE product_id NOT IN (SELECT id FROM products)
        ');

        $this->addSql('
            ALTER TABLE product_reviews
            ADD CONSTRAINT FK_PRODUCT
            FOREIGN KEY (product_id) REFERENCES products(id)
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_reviews DROP FOREIGN KEY FK_PRODUCT');
    }
}
```

### Best Practices

1. **Always write down() method** - Ensure migrations are reversible
2. **Test migrations** - Run up/down/up cycle to verify
3. **Keep migrations small** - One logical change per migration
4. **Use descriptive names** - Version timestamp with clear description
5. **Don't edit executed migrations** - Create new migration to fix issues
6. **Production safety** - Add existence checks for idempotency
7. **Data migrations separately** - Consider separate data migration scripts for large datasets
8. **Backup before migrate** - Always backup production before running migrations
9. **Use transactions** - Enable `all_or_nothing` for atomicity
10. **Don't use entities in migrations** - Schema may not match entity; use raw SQL

### Common Pitfalls

- **Editing already executed migrations** - Never modify migrations after they've been run
- **Not testing down()** - Broken down() prevents rollback
- **Long-running migrations** - May lock tables; consider online schema changes
- **Using entities** - Entity class may not match database state during migration
- **Missing foreign key checks** - Disable during MySQL migrations to avoid constraint errors
- **Platform-specific SQL** - May fail on different databases; use platform checks

### Symfony Integration

```yaml
# config/packages/doctrine_migrations.yaml
doctrine_migrations:
    migrations_paths:
        'App\Migrations': 'migrations'
    enable_profiler: '%kernel.debug%'
```

```bash
# Symfony CLI
php bin/console doctrine:migrations:status
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:rollback
```
