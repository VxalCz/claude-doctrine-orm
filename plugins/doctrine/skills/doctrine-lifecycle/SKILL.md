---
name: doctrine-lifecycle
description: When working with Doctrine entity lifecycle callbacks, events, prePersist, preUpdate, postLoad, or entity listeners
---

## Entity Lifecycle

Doctrine ORM provides a lifecycle system that allows you to hook into entity state transitions. You can use lifecycle callbacks directly in entities or separate event listeners for more complex scenarios.

### Lifecycle Callbacks (In Entity)

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'articles')]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        // Called before entity is persisted for the first time
        $this->slug = $this->generateSlug($this->title);
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        // Called before entity is updated
        $this->updatedAt = new \DateTimeImmutable();
        $this->slug = $this->generateSlug($this->title);
    }

    #[ORM\PostLoad]
    public function onPostLoad(): void
    {
        // Called after entity is loaded from database
        // Useful for initializing transient fields
    }

    private function generateSlug(string $title): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    }
}
```

### Available Lifecycle Events

| Event | Timing | Use Case |
|-------|--------|----------|
| `preRemove` | Before `remove()` called | Cleanup before deletion |
| `postRemove` | After `remove()` + `flush()` | Logging, notifications |
| `prePersist` | Before `persist()` + `flush()` | Set defaults, generate values |
| `postPersist` | After `persist()` + `flush()` | Send notifications |
| `preUpdate` | Before changes flushed | Update timestamps, recalculate |
| `postUpdate` | After changes flushed | Clear cache, notify |
| `postLoad` | After entity loaded | Initialize computed fields |
| `preFlush` | Before any changes flushed | Global validation |
| `onFlush` | During flush, before SQL | Complex changes to other entities |
| `postFlush` | After all changes flushed | Cleanup, logging |
| `onClear` | When `clear()` called | Cleanup transient data |
| `loadClassMetadata` | When metadata loaded | Dynamic mapping changes |

### Entity Listeners (External Class)

For complex logic or reusable listeners across multiple entities:

```php
<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Article;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Psr\Log\LoggerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleListener
{
    public function __construct(
        private SluggerInterface $slugger,
        private LoggerInterface $logger
    ) {}

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Article) {
            return;
        }

        $entity->setSlug(
            (string) $this->slugger->slug($entity->getTitle())->lower()
        );

        $this->logger->info('Creating new article', [
            'title' => $entity->getTitle(),
        ]);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Article) {
            return;
        }

        // Check if specific field changed
        if ($args->hasChangedField('title')) {
            $entity->setSlug(
                (string) $this->slugger->slug($entity->getTitle())->lower()
            );
        }

        $entity->setUpdatedAt(new \DateTimeImmutable());
    }
}
```

**Configuration (Symfony):**

```yaml
# config/services.yaml
services:
    App\EventListener\ArticleListener:
        tags:
            - { name: doctrine.orm.entity_listener, entity: App\Entity\Article, event: prePersist }
            - { name: doctrine.orm.entity_listener, entity: App\Entity\Article, event: preUpdate }
```

**Or using PHP attributes:**

```php
#[ORM\EntityListeners([ArticleListener::class])]
class Article
{
    // ...
}
```

### Event Subscribers (Global Events)

```php
<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Psr\Log\LoggerInterface;

class DoctrineEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $em = $args->getObjectManager();
        $uow = $em->getUnitOfWork();

        // Get all changes
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->logger->info('Entity inserted', [
                'class' => get_class($entity),
            ]);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $changeSet = $uow->getEntityChangeSet($entity);
            $this->logger->info('Entity updated', [
                'class' => get_class($entity),
                'changes' => array_keys($changeSet),
            ]);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->logger->info('Entity deleted', [
                'class' => get_class($entity),
            ]);
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $this->logger->info('Database flush completed');
    }
}
```

### Timestampable Pattern

```php
<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;

trait TimestampableTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}

// Usage
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Product
{
    use TimestampableTrait;

    // ...
}
```

### Blameable Pattern (Track User)

```php
<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait BlameableTrait
{
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $updatedBy = null;

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $user): void
    {
        $this->createdBy = $user;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $user): void
    {
        $this->updatedBy = $user;
    }
}
```

### Soft Delete Pattern

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'products')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    public function softDelete(): void
    {
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function restore(): void
    {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }
}

// Repository method to exclude soft-deleted
class ProductRepository extends ServiceEntityRepository
{
    /**
     * @return Product[]
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->getQuery()
            ->getResult();
    }
}
```

### Best Practices

1. **Keep callbacks simple** - Complex logic belongs in listeners/subscribers
2. **Avoid flush in callbacks** - Can cause infinite loops; use onFlush for complex changes
3. **Use events for cross-cutting concerns** - Logging, auditing, sending notifications
4. **Prefer immutable timestamps** - Use `datetime_immutable` for createdAt/updatedAt
5. **Check entity type in listeners** - Always verify `$entity instanceof YourEntity`
6. **Dependency injection** - Use entity listeners for dependencies, not callbacks
7. **Be careful with onFlush** - Requires recomputeSingleEntityChangeSet() after modifications

### Common Pitfalls

- **Calling flush() in callbacks** - Creates infinite loop or errors
- **Modifying other entities in pre* events** - Use onFlush for this
- **Forgetting HasLifecycleCallbacks** - Attribute required for callbacks to work
- **Not checking instanceof** - Listener may receive different entity types
