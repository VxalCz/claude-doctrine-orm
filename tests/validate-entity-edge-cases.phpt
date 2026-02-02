<?php

declare(strict_types=1);

use Tester\Assert;

require __DIR__ . '/bootstrap.php';

$scriptPath = __DIR__ . '/../plugins/doctrine/hooks/validate-entity.php';
$fixturesDir = __DIR__ . '/fixtures';


test('validates missing namespace', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/no-namespace.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class NoNamespace
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Missing namespace', $result['stderr']);

	unlink($entityFile);
});


test('validates public properties', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/public-props.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class PublicProps
{
	#[ORM\Id]
	#[ORM\Column]
	public int $id;

	#[ORM\Column]
	public string $name;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Public properties', $result['stderr']);

	unlink($entityFile);
});


test('accepts readonly public properties', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/readonly-props.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class ReadonlyProps
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	public readonly string $name;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	// Should pass - readonly public properties are OK
	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});


test('validates unknown column type', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/unknown-type.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class UnknownType
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column(type: 'invalid_type')]
	private string $data;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Unknown column type', $result['stderr']);

	unlink($entityFile);
});


test('validates GeneratedValue without Id', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/generated-no-id.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class GeneratedNoId
{
	#[ORM\Column]
	#[ORM\GeneratedValue]
	private int $id;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('GeneratedValue', $result['stderr']);
	Assert::contains('no #[Id]', $result['stderr']);

	unlink($entityFile);
});


test('validates association with both mappedBy and inversedBy', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/invalid-association.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class InvalidAssoc
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(targetEntity: Other::class, mappedBy: 'invalid', inversedBy: 'alsoInvalid')]
	private $items;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('both mappedBy and inversedBy', $result['stderr']);

	unlink($entityFile);
});


test('validates ManyToOne with mappedBy', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/manytoone-mappedby.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class ManyToOneMappedBy
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\ManyToOne(targetEntity: User::class, mappedBy: 'items')]
	private User $user;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('ManyToOne', $result['stderr']);
	Assert::contains('mappedBy', $result['stderr']);

	unlink($entityFile);
});


test('validates Embeddable with associations', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/embeddable-assoc.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Embeddable]
class EmbeddableAssoc
{
	#[ORM\Column]
	private string $street;

	#[ORM\ManyToOne(targetEntity: City::class)]
	private City $city;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Embeddable', $result['stderr']);
	Assert::contains('cannot contain associations', $result['stderr']);

	unlink($entityFile);
});


test('accepts embeddable without Id', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/valid-embeddable.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Embeddable]
class Address
{
	#[ORM\Column]
	private string $street;

	#[ORM\Column]
	private string $city;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	// Embeddables don't need #[Id]
	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});


test('validates decimal without precision/scale', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/decimal-no-precision.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class DecimalNoPrecision
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column(type: 'decimal')]
	private string $price;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('precision', $result['stderr']);
	Assert::contains('scale', $result['stderr']);

	unlink($entityFile);
});


test('validates PHP type and Doctrine type mismatch', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/type-mismatch.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class TypeMismatch
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column(type: 'datetime_immutable')]
	private string $createdAt;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('PHP type', $result['stderr']);
	Assert::contains('Doctrine type', $result['stderr']);

	unlink($entityFile);
});


test('validates HasLifecycleCallbacks without methods', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/lifecycle-no-methods.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class LifecycleNoMethods
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('HasLifecycleCallbacks', $result['stderr']);
	Assert::contains('no lifecycle callback', $result['stderr']);

	unlink($entityFile);
});


test('accepts valid lifecycle callbacks', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/valid-lifecycle.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class ValidLifecycle
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\PrePersist]
	public function onPrePersist(): void
	{
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});


test('validates repository without return type', function () use ($scriptPath, $fixturesDir) {
	$repoFile = $fixturesDir . '/UserRepository.php';
	file_put_contents($repoFile, <<<'PHP'
<?php
namespace App\Repository;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, User::class);
	}

	public function findByEmail(string $email)
	{
		return $this->createQueryBuilder('u')
			->andWhere('u.email = :email')
			->setParameter('email', $email)
			->getQuery()
			->getOneOrNullResult();
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $repoFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('return type', $result['stderr']);

	unlink($repoFile);
});


test('validates JoinColumn without association', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/joincolumn-no-assoc.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class JoinColumnNoAssoc
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
	private int $userId;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('JoinColumn', $result['stderr']);
	Assert::contains('without accompanying', $result['stderr']);

	unlink($entityFile);
});


test('validates JoinTable without ManyToMany', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/jointable-nomanytomany.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class JoinTableNoManyToMany
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	#[ORM\JoinTable(name: 'tags')]
	private string $tag;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('JoinTable', $result['stderr']);
	Assert::contains('without', $result['stderr']);

	unlink($entityFile);
});


test('validates OrderBy without to-many association', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/orderby-no-tomany.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class OrderByNoToMany
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\ManyToOne(targetEntity: User::class)]
	#[ORM\OrderBy(['name' => 'ASC'])]
	private User $user;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('OrderBy', $result['stderr']);
	Assert::contains('without', $result['stderr']);

	unlink($entityFile);
});


test('validates SequenceGenerator without GeneratedValue', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/sequence-no-generated.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class SequenceNoGenerated
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\SequenceGenerator(sequenceName: 'user_seq', allocationSize: 1)]
	private int $id;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('SequenceGenerator', $result['stderr']);
	Assert::contains('GeneratedValue', $result['stderr']);

	unlink($entityFile);
});


test('validates association without targetEntity', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/no-target-entity.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class NoTargetEntity
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(mappedBy: 'user')]
	private $items;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('targetEntity', $result['stderr']);

	unlink($entityFile);
});


test('validates JoinColumn without association', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/joincolumn-no-assoc.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class JoinColumnNoAssoc
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	#[ORM\JoinColumn]
	private string $data;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('JoinColumn', $result['stderr']);
	Assert::contains('without', $result['stderr']);

	unlink($entityFile);
});


test('validates JoinTable without ManyToMany', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/jointable-no-mtm.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class JoinTableNoMtm
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	#[ORM\JoinTable]
	private string $data;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('JoinTable', $result['stderr']);
	Assert::contains('ManyToMany', $result['stderr']);

	unlink($entityFile);
});


test('validates OrderBy without to-many association', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/orderby-no-tomany.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class OrderByNoToMany
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\Column]
	#[ORM\OrderBy]
	private string $data;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('OrderBy', $result['stderr']);

	unlink($entityFile);
});


test('validates SequenceGenerator without GeneratedValue', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/seqgen-no-genval.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class SeqGenNoGenVal
{
	#[ORM\Id]
	#[ORM\Column]
	#[ORM\SequenceGenerator]
	private int $id;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('SequenceGenerator', $result['stderr']);
	Assert::contains('GeneratedValue', $result['stderr']);

	unlink($entityFile);
});


test('validates missing targetEntity in association', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/no-target-entity.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
class NoTargetEntity
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany]
	private $items;
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('targetEntity', $result['stderr']);

	unlink($entityFile);
});


test('validates Collection without initialization', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/collection-no-init.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity]
class CollectionNoInit
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'parent')]
	private Collection $items;

	public function __construct()
	{
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('Collection', $result['stderr']);
	Assert::contains('initialized', $result['stderr']);

	unlink($entityFile);
});


test('validates OneToMany without mappedBy', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/oneto-many-no-mappedby.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity]
class OneToManyNoMappedBy
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(targetEntity: Item::class)]
	private Collection $items;

	public function __construct()
	{
		$this->items = new ArrayCollection();
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	Assert::same(2, $result['exitCode']);
	Assert::contains('OneToMany', $result['stderr']);
	Assert::contains('mappedBy', $result['stderr']);

	unlink($entityFile);
});


test('accepts valid OneToMany with mappedBy', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/valid-onetomany.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity]
class ValidOneToMany
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'parent')]
	private Collection $items;

	public function __construct()
	{
		$this->items = new ArrayCollection();
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	// Should pass - OneToMany has mappedBy and Collection is initialized
	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});


test('accepts Collection initialized with ArrayCollection', function () use ($scriptPath, $fixturesDir) {
	$entityFile = $fixturesDir . '/collection-init.php';
	file_put_contents($entityFile, <<<'PHP'
<?php
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
#[ORM\Entity]
class CollectionInit
{
	#[ORM\Id]
	#[ORM\Column]
	private int $id;

	#[ORM\OneToMany(targetEntity: Item::class, mappedBy: 'parent')]
	private Collection $items;

	public function __construct()
	{
		$this->items = new ArrayCollection();
	}
}
PHP
	);

	$result = runHookScript($scriptPath, [
		'tool_input' => ['file_path' => $entityFile],
		'cwd' => $fixturesDir,
	]);

	// Should pass - Collection is properly initialized
	Assert::same(0, $result['exitCode']);

	unlink($entityFile);
});
