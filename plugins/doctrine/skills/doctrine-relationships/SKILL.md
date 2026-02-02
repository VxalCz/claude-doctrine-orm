---
name: doctrine-relationships
description: When working with Doctrine entity relationships, associations, joins, OneToOne, OneToMany, ManyToOne, ManyToMany mappings
---

## Doctrine Entity Relationships

Doctrine supports all standard relationship types: One-to-One, One-to-Many, Many-to-One, and Many-to-Many.

### Many-to-One (Single Direction)

Most common relationship - many entities belong to one parent:

```php
#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'id', nullable: false)]
    private User $author;

    public function __construct(string $title, User $author)
    {
        $this->title = $title;
        $this->author = $author;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }
}

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'author')]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): void
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setAuthor($this);
        }
    }

    public function removeArticle(Article $article): void
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getAuthor() === $this) {
                $article->setAuthor(null);
            }
        }
    }
}
```

### One-to-Many (Bidirectional)

Parent entity has many children:

```php
#[ORM\Entity]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }
    }

    public function removeItem(OrderItem $item): void
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
    }
}

#[ORM\Entity]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $productName;

    #[ORM\Column(type: 'integer')]
    private int $quantity;

    public function setOrder(?Order $order): void
    {
        $this->order = $order;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }
}
```

### Many-to-Many

Many entities related to many others:

```php
#[ORM\Entity]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'articles')]
    #[ORM\JoinTable(name: 'article_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function addTag(Tag $tag): void
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->addArticle($this);
        }
    }

    public function removeTag(Tag $tag): void
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removeArticle($this);
        }
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }
}

#[ORM\Entity]
class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $name;

    #[ORM\ManyToMany(targetEntity: Article::class, mappedBy: 'tags')]
    private Collection $articles;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->articles = new ArrayCollection();
    }

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): void
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
        }
    }

    public function removeArticle(Article $article): void
    {
        $this->articles->removeElement($article);
    }
}
```

### Many-to-Many with Extra Fields

When you need extra columns on the join table:

```php
#[ORM\Entity]
class Student
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'student', orphanRemoval: true)]
    private Collection $enrollments;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
    }
}

#[ORM\Entity]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToMany(targetEntity: Enrollment::class, mappedBy: 'course', orphanRemoval: true)]
    private Collection $enrollments;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
    }
}

#[ORM\Entity]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Student::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private Student $student;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private Course $course;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column(type: 'string', length: 20)]
    private string $grade;

    public function __construct(Student $student, Course $course)
    {
        $this->student = $student;
        $this->course = $course;
        $this->enrolledAt = new \DateTimeImmutable();
    }
}
```

### One-to-One

Single relationship in both directions:

```php
#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Profile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Profile $profile = null;

    public function setProfile(Profile $profile): void
    {
        $this->profile = $profile;
        $profile->setUser($this);
    }
}

#[ORM\Entity]
class Profile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'profile')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
```

### Eager vs Lazy Loading

```php
// Lazy loading (default) - loads relationship on access
#[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
private User $author;

// Eager loading - loads with parent entity
#[ORM\ManyToOne(targetEntity: User::class, fetch: 'EAGER')]
private User $author;

// Extra lazy - count/size without loading collection
#[ORM\OneToMany(targetEntity: Article::class, mappedBy: 'author', fetch: 'EXTRA_LAZY')]
private Collection $articles;
```

### Cascade Operations

```php
#[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
private Collection $items;

// Available cascades:
// - 'persist' - Persist related entities
// - 'remove' - Delete related entities
// - 'merge' - Merge related entities
// - 'detach' - Detach related entities
// - 'refresh' - Refresh related entities
// - 'all' - All of the above
```

### Join Column Options

```php
#[ORM\JoinColumn(
    name: 'user_id',           // Column name in database
    referencedColumnName: 'id', // Referenced column in target table
    nullable: false,           // NOT NULL constraint
    onDelete: 'CASCADE',       // Database-level cascade
    onUpdate: 'CASCADE',       // Database-level update cascade
    unique: true               // For OneToOne relationships
)]
private User $user;
```

### Best Practices

1. **Bidirectional by Default** - Easier to navigate from either side
2. **Owning Side** - Many-to-One is always the owning side
3. **Orphan Removal** - Use for compositions (e.g., Order → OrderItem)
4. **Lazy Loading** - Default is fine for most cases
5. **Collection Type** - Always use `Collection` interface, initialize in constructor
6. **Add/Remove Methods** - Implement both sides of bidirectional relationships
7. **Database Cascades** - Use `onDelete: 'CASCADE'` for referential integrity
8. **Avoid EAGER** - Can cause performance issues with complex graphs
