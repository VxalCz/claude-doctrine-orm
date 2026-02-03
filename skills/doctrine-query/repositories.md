---
name: doctrine-query-repositories
description: When working with custom Doctrine repositories, repository patterns, or extending ServiceEntityRepository
---

# Custom Repositories

## ServiceEntityRepository Pattern

Extend `ServiceEntityRepository` for type-safe queries:

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

## Repository Best Practices

1. **Always extend ServiceEntityRepository** - Provides base functionality
2. **Use phpDoc @extends** - Enables IDE autocompletion
3. **Return typed results** - `?User` vs `array`
4. **Name methods descriptively** - `findActiveUsers()` vs `findBy()`
5. **Encapsulate complex queries** - Hide QueryBuilder details
