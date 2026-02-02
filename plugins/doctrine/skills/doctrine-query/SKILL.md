---
name: doctrine-query
description: When working with DQL queries, QueryBuilder, custom repositories, or complex database queries in Doctrine
---

## Doctrine Querying

Doctrine provides multiple ways to query data: Repository methods, QueryBuilder, and DQL.

### Repository Methods

```php
// Find by primary key
$user = $userRepository->find(1);

// Find all
$users = $userRepository->findAll();

// Find by criteria
$users = $userRepository->findBy(['status' => 'active']);

// Find one by criteria
$user = $userRepository->findOneBy(['email' => 'john@example.com']);

// Find with ordering and limit
$users = $userRepository->findBy(
    ['status' => 'active'],
    ['createdAt' => 'DESC'],
    10,  // Limit
    0    // Offset
);
```

### QueryBuilder

Fluent API for building queries:

```php
<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveUsersQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('u.createdAt', 'DESC');
    }

    public function findBySearchTerm(string $term): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.name LIKE :term OR u.email LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithOrders(): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.orders', 'o')
            ->andWhere('o.status = :status')
            ->setParameter('status', 'completed')
            ->addSelect('o') // Eager load orders
            ->getQuery()
            ->getResult();
    }

    public function getUserStatistics(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.status, COUNT(u.id) as count')
            ->groupBy('u.status')
            ->getQuery()
            ->getResult();
    }

    public function findRecentUsers(\DateTimeImmutable $since): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.createdAt >= :since')
            ->setParameter('since', $since)
            ->getQuery()
            ->getResult();
    }
}
```

### DQL (Doctrine Query Language)

SQL-like language for querying objects:

```php
// Simple DQL
$dql = "SELECT u FROM App\Entity\User u WHERE u.status = :status";
$query = $entityManager->createQuery($dql);
$query->setParameter('status', 'active');
$users = $query->getResult();

// DQL with joins
$dql = "SELECT u, o FROM App\Entity\User u
        JOIN u.orders o
        WHERE o.total > :minTotal
        ORDER BY o.createdAt DESC";
$query = $entityManager->createQuery($dql);
$query->setParameter('minTotal', 100);
$results = $query->getResult();

// Named query in repository
public function findHighValueCustomers(float $minTotal): array
{
    $dql = "SELECT u, SUM(o.total) as totalSpent
            FROM App\Entity\User u
            JOIN u.orders o
            WHERE o.status = 'completed'
            GROUP BY u.id
            HAVING totalSpent > :minTotal";

    return $this->getEntityManager()
        ->createQuery($dql)
        ->setParameter('minTotal', $minTotal)
        ->getResult();
}
```

### Native SQL

For complex queries that DQL can't handle:

```php
use Doctrine\ORM\Query\ResultSetMapping;

$rsm = new ResultSetMapping();
$rsm->addEntityResult(User::class, 'u');
$rsm->addFieldResult('u', 'id', 'id');
$rsm->addFieldResult('u', 'name', 'name');
$rsm->addFieldResult('u', 'email', 'email');

$sql = 'SELECT u.*, COUNT(o.id) as order_count
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        WHERE u.status = ?
        GROUP BY u.id';

$query = $entityManager->createNativeQuery($sql, $rsm);
$query->setParameter(1, 'active');

$users = $query->getResult();
```

### Hydration Modes

```php
// Default - returns entity objects
$users = $query->getResult();

// Array hydration - returns arrays (faster, no entity tracking)
$users = $query->getResult(Query::HYDRATE_ARRAY);

// Scalar hydration - single values
$count = $query->getSingleScalarResult();

// Get single result (throws if none or multiple)
$user = $query->getSingleResult();

// Get one or null
$user = $query->getOneOrNullResult();

// Iterate large results (memory efficient)
$iterable = $query->toIterable();
foreach ($iterable as $user) {
    // Process user
    $entityManager->detach($user); // Free memory
}
```

### Query Hints

```php
// Read-only query (improves performance)
$query = $repository->createQueryBuilder('u')
    ->setHint(Query::HINT_READ_ONLY, true)
    ->getQuery();

// Force partial load
$query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

// Custom tree walker for complex SQL generation
$query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, [CustomTreeWalker::class]);
```

### Paging Results

```php
use Doctrine\ORM\Tools\Pagination\Paginator;

public function findPaginated(int $page = 1, int $perPage = 20): Paginator
{
    $query = $this->createQueryBuilder('u')
        ->orderBy('u.createdAt', 'DESC')
        ->setFirstResult(($page - 1) * $perPage)
        ->setMaxResults($perPage)
        ->getQuery();

    return new Paginator($query);
}

// Usage
$paginator = $repository->findPaginated(1, 20);
$totalItems = count($paginator);
$users = iterator_to_array($paginator);
```

### Best Practices

1. **Use Repository Methods** - Encapsulate queries in repository classes
2. **QueryBuilder over DQL** - More maintainable, IDE-friendly
3. **Parameter Binding** - Always use parameters to prevent SQL injection
4. **Add Select for Joins** - Eager load with `addSelect()` to avoid N+1
5. **Pagination** - Use `Paginator` for large result sets
6. **Hydration Mode** - Use array hydration for read-only data
7. **Iterate Large Results** - Use `toIterable()` for memory efficiency
