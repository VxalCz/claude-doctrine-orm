---
name: doctrine-orm
description: When working with Doctrine ORM entities, repositories, entity manager, persistence operations, or mapping configuration
---

## Doctrine ORM Overview

Doctrine ORM (Object-Relational Mapping) maps PHP objects to database tables, allowing you to work with database records as objects.

### Entity Structure

An entity is a PHP class with attributes/annotations defining its database mapping:

```php
<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column(type: 'integer')]
	private ?int $id = null;

	#[ORM\Column(type: 'string', length: 255)]
	private string $name;

	#[ORM\Column(type: 'string', length: 255, unique: true)]
	private string $email;

	#[ORM\Column(type: 'datetime_immutable')]
	private \DateTimeImmutable $createdAt;

	public function __construct(string $name, string $email)
	{
		$this->name = $name;
		$this->email = $email;
		$this->createdAt = new \DateTimeImmutable();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getEmail(): string
	{
		return $this->email;
	}

	public function setEmail(string $email): void
	{
		$this->email = $email;
	}

	public function getCreatedAt(): \DateTimeImmutable
	{
		return $this->createdAt;
	}
}
```

### Repository Pattern

Repositories encapsulate entity queries:

```php
<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, User::class);
	}

	public function findByEmail(string $email): ?User
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.email = :email')
			->setParameter('email', $email)
			->getQuery()
			->getOneOrNullResult();
	}

	/**
	 * @return User[]
	 */
	public function findActiveUsers(): array
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.isActive = :active')
			->setParameter('active', true)
			->orderBy('u.name', 'ASC')
			->getQuery()
			->getResult();
	}
}
```

### Entity Manager Operations

```php
// Persist (insert)
$user = new User('John', 'john@example.com');
$entityManager->persist($user);
$entityManager->flush();

// Update
$user = $userRepository->find(1);
$user->setName('Jane');
$entityManager->flush(); // No persist needed for updates

// Remove
$entityManager->remove($user);
$entityManager->flush();

// Refresh from database
$entityManager->refresh($user);

// Detach from entity manager
$entityManager->detach($user);

// Clear entity manager (detaches all entities)
$entityManager->clear();
```

### Common Column Types

| Type | PHP Type | Description |
|------|----------|-------------|
| `integer` | `int` | Whole numbers |
| `string` | `string` | Variable length string (use `length` parameter) |
| `text` | `string` | Unlimited length text |
| `boolean` | `bool` | True/false |
| `datetime` | `DateTime` | Date and time |
| `datetime_immutable` | `DateTimeImmutable` | Immutable date and time (recommended) |
| `date` | `DateTime` | Date only |
| `time` | `DateTime` | Time only |
| `decimal` | `string` | Precise decimal (use `precision` and `scale`) |
| `float` | `float` | Floating point |
| `json` | `array` | JSON encoded array/object |
| `uuid` | `UuidInterface` | UUID (requires ramsey/uuid) |
| `enum` | `BackedEnum` | PHP 8.1 enum |

### Column Constraints

```php
#[ORM\Column(type: 'string', length: 255, unique: true)]
private string $email;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $middleName = null;

#[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
private string $price; // Decimals are strings for precision

#[ORM\Column(type: 'string', length: 255, options: ['default' => 'active'])]
private string $status = 'active';
```

### Best Practices

1. **Use PHP 8 Attributes** - Modern, type-safe mapping (PHP 8+)
2. **Immutable DateTimes** - Prefer `datetime_immutable` over `datetime`
3. **Constructor Initialization** - Initialize collections and required fields in constructor
4. **Nullable Types** - Mark nullable properties with `?` type and default to `null`
5. **Private Properties** - Keep properties private, expose via getters/setters
6. **Return Types** - Always add return type hints
7. **No Setter for ID** - Primary key should never have a setter
