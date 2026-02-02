---
name: doctrine-mapping
description: When configuring Doctrine entity mapping with attributes, XML, YAML, or working with column definitions and field mappings
---

## Doctrine Mapping

Doctrine supports multiple mapping drivers: PHP 8 Attributes (recommended), XML, and YAML.

### PHP 8 Attributes (Recommended)

Modern, type-safe approach using PHP 8 attributes:

```php
<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users', indexes: [
    new ORM\Index(columns: ['email'], name: 'email_idx'),
    new ORM\Index(columns: ['status', 'created_at'], name: 'status_created_idx')
])]
#[ORM\UniqueConstraint(name: 'unique_username', columns: ['username'])]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $username;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    private int $loginCount = 0;
}
```

### XML Mapping

Useful for separating mapping from entities:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                  xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                          https://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

    <entity name="App\Entity\User" table="users" repository-class="App\Repository\UserRepository">
        <indexes>
            <index name="email_idx" columns="email"/>
        </indexes>

        <id name="id" type="integer">
            <generator strategy="AUTO"/>
        </id>

        <field name="username" type="string" length="255"/>
        <field name="email" type="string" length="255" unique="true"/>
        <field name="bio" type="string" length="1000" nullable="true"/>
        <field name="loginCount" type="integer">
            <options>
                <option name="unsigned">true</option>
            </options>
        </field>
        <field name="createdAt" type="datetime_immutable"/>
    </entity>
</doctrine-mapping>
```

### YAML Mapping

```yaml
App\Entity\User:
    type: entity
    table: users
    repositoryClass: App\Repository\UserRepository
    indexes:
        email_idx:
            columns: [ email ]
    id:
        id:
            type: integer
            generator:
                strategy: AUTO
    fields:
        username:
            type: string
            length: 255
        email:
            type: string
            length: 255
            unique: true
        bio:
            type: string
            length: 1000
            nullable: true
        loginCount:
            type: integer
            options:
                unsigned: true
        createdAt:
            type: datetime_immutable
```

### Generation Strategies

```php
// AUTO - Database chooses (recommended for most cases)
#[ORM\GeneratedValue(strategy: 'AUTO')]

// IDENTITY - Auto-increment (MySQL) / SERIAL (PostgreSQL)
#[ORM\GeneratedValue(strategy: 'IDENTITY')]

// SEQUENCE - Database sequence (Oracle, PostgreSQL)
#[ORM\GeneratedValue(strategy: 'SEQUENCE')]
#[ORM\SequenceGenerator(sequenceName: 'user_seq', allocationSize: 1)]

// UUID - Use UUID instead of auto-increment
#[ORM\GeneratedValue(strategy: 'UUID')]

// NONE - No auto-generation, must set manually
#[ORM\GeneratedValue(strategy: 'NONE')]

// CUSTOM - Custom generator
#[ORM\GeneratedValue(strategy: 'CUSTOM')]
#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
```

### Embeddable Objects

Reusable value objects embedded in entities:

```php
#[ORM\Embeddable]
class Address
{
    #[ORM\Column(type: 'string', length: 255)]
    private string $street;

    #[ORM\Column(type: 'string', length: 255)]
    private string $city;

    #[ORM\Column(type: 'string', length: 20)]
    private string $postalCode;
}

#[ORM\Entity]
class User
{
    // ...

    #[ORM\Embedded(columnPrefix: 'address_')]
    private Address $address;
}
```

### Enum Mapping (PHP 8.1+)

```php
enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Banned = 'banned';
}

#[ORM\Entity]
class User
{
    // ...

    #[ORM\Column(type: 'string', enumType: UserStatus::class)]
    private UserStatus $status = UserStatus::Active;
}
```

### Best Practices

1. **Prefer Attributes** - Use PHP 8 attributes for new projects (clean, IDE-friendly)
2. **Use Separate Files for XML/YAML** - Keeps entities clean of mapping logic
3. **Consistent Naming** - Use singular table names, lowercase with underscores
4. **Indexes** - Add indexes for frequently queried columns
5. **Column Lengths** - Always specify length for string columns
6. **Unsigned Integers** - Use for IDs and counters where negative values don't make sense
