---
name: doctrine-orm-types
description: When working with Doctrine column types, custom DBAL types, or field mappings
---

# Doctrine Column Types

## Scalar Types

| Type | PHP Type | Notes |
|------|----------|-------|
| `integer` | `int` | 32-bit signed |
| `smallint` | `int` | 16-bit signed |
| `bigint` | `string` | 64-bit as string |
| `string` | `string` | Requires `length` parameter |
| `text` | `string` | Unlimited length (CLOB) |
| `ascii_string` | `string` | Platform ASCII |
| `binary` | `string` | Binary string |
| `blob` | `string` | Binary large object |

## Boolean Types

```php
#[ORM\Column(type: 'boolean')]
private bool $isActive;
```

## Numeric Types

```php
// Decimal for precise calculations (money)
#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
private string $price;

// Float for scientific calculations
#[ORM\Column(type: 'float')]
private float $weight;
```

## Date/Time Types

```php
// Immutable types (recommended)
#[ORM\Column(type: 'datetime_immutable')]
private \DateTimeImmutable $createdAt;

// Mutable types
#[ORM\Column(type: 'datetime')]
private \DateTime $updatedAt;

// Date or time only
#[ORM\Column(type: 'date_immutable')]
private \DateTimeImmutable $birthDate;

#[ORM\Column(type: 'time_immutable')]
private \DateTimeImmutable $startTime;
```

## Array Types

```php
// Simple array (comma-separated)
#[ORM\Column(type: 'simple_array')]
private array $tags;

// JSON (associative array)
#[ORM\Column(type: 'json')]
private array $metadata;
```

## Identity Types

```php
// UUID (requires ramsey/uuid)
#[ORM\Column(type: 'uuid')]
private UuidInterface $uuid;

// ULID
#[ORM\Column(type: 'ulid')]
private Ulid $ulid;

// GUID string
#[ORM\Column(type: 'guid')]
private string $guid;
```

## Enum Type (PHP 8.1+)

```php
enum Status: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
}

#[ORM\Column(type: 'string', enumType: Status::class)]
private Status $status;
```

## Column Options

```php
#[ORM\Column(
    type: 'string',
    length: 255,
    nullable: true,
    unique: true,
    options: ['default' => 'pending', 'collation' => 'utf8mb4_unicode_ci']
)]
private ?string $code = null;
```
