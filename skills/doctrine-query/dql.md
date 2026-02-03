---
name: doctrine-query-dql
description: When writing DQL queries, complex joins, or using Doctrine Query Language
---

# DQL (Doctrine Query Language)

SQL-like syntax for object queries:

## Basic DQL

```php
// Simple query
$dql = "SELECT u FROM App\Entity\User u WHERE u.status = :status";
$query = $entityManager->createQuery($dql);
$query->setParameter('status', 'active');
$users = $query->getResult();
```

## Joins

```php
// Inner join
$dql = "SELECT u FROM App\Entity\User u
        JOIN u.orders o
        WHERE o.total > :minTotal";

// Left join with partial data
$dql = "SELECT u, o FROM App\Entity\User u
        LEFT JOIN u.orders o
        WHERE o.status IS NULL OR o.status = :status";

// Join with collection condition
$dql = "SELECT u FROM App\Entity\User u
        JOIN u.tags t
        WHERE t.name IN (:tags)";
```

## Aggregations

```php
// Group by with aggregation
$dql = "SELECT u.status, COUNT(u.id) as cnt
        FROM App\Entity\User u
        GROUP BY u.status";

// Having clause
$dql = "SELECT u, SUM(o.total) as totalSpent
        FROM App\Entity\User u
        JOIN u.orders o
        GROUP BY u.id
        HAVING totalSpent > :minTotal";
```

## Subqueries

```php
// IN subquery
$dql = "SELECT u FROM App\Entity\User u
        WHERE u.id IN (
            SELECT IDENTITY(o.user) FROM App\Entity\Order o
            WHERE o.total > 100
        )";
```

## Best Practices

1. **Use aliases** - `u` for User, `o` for Order
2. **Always use parameters** - Prevent DQL injection
3. **Prefer QueryBuilder** - More maintainable for complex queries
4. **SELECT partial when needed** - `SELECT partial u.{id, name}`
