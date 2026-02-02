<?php

declare(strict_types=1);

/**
 * PostToolUse hook: Validates Doctrine entity files for common issues
 * Based on Doctrine\ORM\Tools\SchemaValidator logic
 */

$jsonInput = file_get_contents('php://stdin');
$input = json_decode($jsonInput);

// Handle empty or invalid JSON gracefully
if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
	exit(0);
}

$filePath = $input->tool_input->file_path ?? '';
$cwd = $input->cwd ?? '';

// Early exit if not a PHP file
if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'php') {
	exit(0);
}

// Check if file exists
if (!file_exists($filePath)) {
	exit(0);
}

// Check if file is in Entity directory or contains Entity namespace
$content = file_get_contents($filePath);
if ($content === false) {
	exit(0);
}

// Skip if not an entity file (no Entity namespace or directory)
if (!isEntityFile($filePath, $content)) {
	exit(0);
}

$errors = [];

// Check PHP syntax first
exec('php -l ' . escapeshellarg($filePath) . ' 2>&1', $output, $exitCode);
if ($exitCode !== 0) {
	$errors[] = "PHP syntax error:\n" . implode("\n", $output);
}

// Check for common entity issues
$errors = array_merge($errors, validateEntity($content, $filePath));

// Check for mapping consistency issues
$errors = array_merge($errors, validateMappingConsistency($content, $filePath));

// Check for type compatibility
$errors = array_merge($errors, validateTypeCompatibility($content, $filePath));

if (count($errors) > 0) {
	fwrite(STDERR, "Entity validation errors in " . basename($filePath) . ":\n");
	fwrite(STDERR, implode("\n\n", $errors) . "\n");
	exit(2);
}

exit(0);

/**
 * Check if file is a Doctrine entity
 */
function isEntityFile(string $filePath, string $content): bool
{
	// Check if in Entity directory
	if (strpos($filePath, '/Entity/') !== false || strpos($filePath, '\\Entity\\') !== false) {
		return true;
	}

	// Check for Entity namespace
	if (preg_match('/namespace\s+[^;]+Entity[;\\\\]/', $content)) {
		return true;
	}

	// Check for Entity attribute or annotation
	if (preg_match('/#\[\s*(?:ORM\\\\)?Entity\s*\]|@(?:ORM\\\\)?Entity\b/', $content)) {
		return true;
	}

	// Check for Embeddable (also ORM mapping)
	if (preg_match('/#\[\s*(?:ORM\\\\)?Embeddable\s*\]|@(?:ORM\\\\)?Embeddable\b/', $content)) {
		return true;
	}

	// Check for Repository class
	if (preg_match('/class\s+\w+Repository\s+extends/', $content)) {
		return true;
	}

	return false;
}

/**
 * Validate entity content for common issues
 */
function validateEntity(string $content, string $filePath): array
{
	$errors = [];
	$className = extractClassName($content);

	// Check for entity without id (skip for Embeddable)
	if (!preg_match('/#\[\s*(?:ORM\\\)?Embeddable\s*\]|@(?:ORM\\\)?Embeddable\b/', $content)) {
		if (!preg_match('/#\[\s*(?:ORM\\\)?Id\s*\]|@(?:ORM\\\)?Id\b/', $content)) {
			$errors[] = "Missing #[Id] attribute - entity must have a primary key";
		}
	}

	// Check for GeneratedValue without Id
	if (preg_match('/#\[\s*(?:ORM\\\)?GeneratedValue|@(?:ORM\\\)?GeneratedValue/', $content)) {
		if (!preg_match('/#\[\s*(?:ORM\\\)?Id\s*\]|@(?:ORM\\\)?Id\b/', $content)) {
			$errors[] = "#[GeneratedValue] found but no #[Id] attribute - #[GeneratedValue] must be paired with #[Id]";
		}
	}

	// Check for SequenceGenerator without GeneratedValue
	if (preg_match('/#\[\s*(?:ORM\\\)?SequenceGenerator|@(?:ORM\\\)?SequenceGenerator/', $content)) {
		if (!preg_match('/#\[\s*(?:ORM\\\)?GeneratedValue\s*\([^)]*\)?\s*\]/', $content)
		    && !preg_match('/@(?:ORM\\\)?GeneratedValue/', $content)) {
			$errors[] = "#[SequenceGenerator] must be used with #[GeneratedValue(strategy: 'SEQUENCE')]";
		}
	}

	// Check for entity without namespace
	if (!preg_match('/namespace\s+\S+;/', $content)) {
		$errors[] = "Missing namespace declaration - entities should be namespaced";
	}

	// Check for public properties (should be private/protected with getters/setters)
	if (preg_match('/\bpublic\s+(?:readonly\s+)?(?:\??\w+\s+)?\$\w+/m', $content)) {
		// Allow if it's a readonly public property (PHP 8.1+)
		if (!preg_match('/\bpublic\s+readonly\s+/', $content)) {
			$errors[] = "Public properties detected - consider using private/protected properties with getters/setters or readonly public properties";
		}
	}

	// Check for string column without length
	if (preg_match_all('/#\[\s*(?:ORM\\\)?Column\s*\(\s*[^)]*type:\s*[\'"]string[\'"][^)]*\)\s*\]/', $content, $matches)) {
		foreach ($matches[0] as $match) {
			if (!preg_match('/length:\s*\d+/', $match)) {
				$errors[] = "String column without length - consider adding length: 255 (or appropriate value) to #[Column]";
				break; // Report once per file
			}
		}
	}

	// Check for invalid column types
	$validTypes = [
		'tinyint', 'smallint', 'integer', 'int', 'bigint',
		'string', 'text', 'guid', 'binary', 'blob',
		'boolean', 'decimal', 'float', 'double',
		'datetime', 'datetime_immutable', 'datetimetz', 'datetimetz_immutable',
		'date', 'date_immutable', 'time', 'time_immutable',
		'array', 'simple_array', 'json', 'json_object',
		'object', 'uuid', 'ulid', 'dateinterval',
		'enum'
	];
	if (preg_match_all('/#\[\s*(?:ORM\\\)?Column\s*\(\s*[^)]*type:\s*[\'"](\w+)[\'"][^)]*\)/', $content, $matches)) {
		foreach ($matches[1] as $type) {
			if (!in_array(strtolower($type), $validTypes, true)) {
				$errors[] = "Unknown column type '$type' - check Doctrine documentation for valid types";
			}
		}
	}

	// Check for HasLifecycleCallbacks without any lifecycle methods
	if (preg_match('/#\[\s*(?:ORM\\\\)?HasLifecycleCallbacks\s*\]|@(?:ORM\\\\)?HasLifecycleCallbacks/', $content)) {
		$lifecycleMethods = [
			'/\#\[\s*(?:ORM\\\\)?PrePersist\s*\]/', '/\#\[\s*(?:ORM\\\\)?PostPersist\s*\]/',
			'/\#\[\s*(?:ORM\\\\)?PreUpdate\s*\]/', '/\#\[\s*(?:ORM\\\\)?PostUpdate\s*\]/',
			'/\#\[\s*(?:ORM\\\\)?PreRemove\s*\]/', '/\#\[\s*(?:ORM\\\\)?PostRemove\s*\]/',
			'/\#\[\s*(?:ORM\\\\)?PostLoad\s*\]/', '/\#\[\s*(?:ORM\\\\)?PreFlush\s*\]/',
		];
		$hasLifecycleMethod = false;
		foreach ($lifecycleMethods as $pattern) {
			if (preg_match($pattern, $content)) {
				$hasLifecycleMethod = true;
				break;
			}
		}
		if (!$hasLifecycleMethod) {
			$errors[] = "#[HasLifecycleCallbacks] declared but no lifecycle callback methods (#[PrePersist], #[PostUpdate], etc.) found";
		}
	}

	// Repository method checks
	if (preg_match('/class\s+\w+Repository\s+extends/', $content)) {
		// Check for find methods without return types
		if (preg_match('/function\s+find\w*\s*\([^)]*\)\s*(?!\s*:)/', $content)) {
			$errors[] = "Repository find method missing return type - add return type hint (?Entity or array) for better type safety";
		}
	}

	return $errors;
}

/**
 * Validate mapping consistency (mappedBy/inversedBy)
 */
function validateMappingConsistency(string $content, string $filePath): array
{
	$errors = [];

	// Extract all associations with mappedBy and inversedBy
	$associations = [];

	// Pattern to match association attributes (parentheses are optional)
	$associationPatterns = [
		'OneToOne' => '/#\[\s*(?:ORM\\\)?OneToOne(?:\s*\(([^)]*)\))?/',
		'OneToMany' => '/#\[\s*(?:ORM\\\)?OneToMany(?:\s*\(([^)]*)\))?/',
		'ManyToOne' => '/#\[\s*(?:ORM\\\)?ManyToOne(?:\s*\(([^)]*)\))?/',
		'ManyToMany' => '/#\[\s*(?:ORM\\\)?ManyToMany(?:\s*\(([^)]*)\))?/',
	];

	foreach ($associationPatterns as $type => $pattern) {
		if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$args = $match[1] ?? '';
				$mappedBy = null;
				$inversedBy = null;
				$targetEntity = null;

				// Extract mappedBy
				if (preg_match('/mappedBy:\s*[\'"](\w+)[\'"]/', $args, $m)) {
					$mappedBy = $m[1];
				}

				// Extract inversedBy
				if (preg_match('/inversedBy:\s*[\'"](\w+)[\'"]/', $args, $m)) {
					$inversedBy = $m[1];
				}

				// Extract targetEntity - supports both named and positional arguments
				if (preg_match('/targetEntity:\s*(?:[\w\\\\]+\\\)?(\w+)(?:::class)?/', $args, $m)) {
					$targetEntity = $m[1];
				} elseif (preg_match('/^\s*([A-Z][\w\\\\]*)(?:::class)?(?:\s*[,)]|$)/', $args, $m)) {
					// Positional first argument - class name starting with uppercase
					$targetEntity = preg_replace('/^.*\\\\/', '', $m[1]);
				}

				$associations[] = [
					'type' => $type,
					'mappedBy' => $mappedBy,
					'inversedBy' => $inversedBy,
					'targetEntity' => $targetEntity,
					'raw' => $args,
				];
			}
		}
	}

	// Check for invalid combinations
	foreach ($associations as $assoc) {
		// Check for both mappedBy and inversedBy on same association
		if ($assoc['mappedBy'] && $assoc['inversedBy']) {
			$errors[] = "Association cannot have both mappedBy and inversedBy - one must be on each side of the relationship";
		}

		// ManyToOne should not have mappedBy (it's always the owning side)
		if ($assoc['type'] === 'ManyToOne' && $assoc['mappedBy']) {
			$errors[] = "ManyToOne association should not have mappedBy - ManyToOne is always the owning side, use inversedBy on the OneToMany side";
		}

		// OneToMany should have mappedBy (it's the inverse side)
		if ($assoc['type'] === 'OneToMany' && !$assoc['mappedBy']) {
			$errors[] = "OneToMany association should have mappedBy pointing to the ManyToOne property on the target entity";
		}

		// Check for missing targetEntity
		if (!$assoc['targetEntity']) {
			$errors[] = "{$assoc['type']} association missing targetEntity - specify the entity class to link to";
		}
	}

	// Check Embeddable has no associations
	if (preg_match('/#\[\s*(?:ORM\\\)?Embeddable|@(?:ORM\\\)?Embeddable/', $content)) {
		foreach ($associationPatterns as $type => $pattern) {
			if (preg_match($pattern, $content)) {
				$errors[] = "Embeddable class cannot contain associations ($type) - Embeddables are value objects without relationships";
				break;
			}
		}
	}

	// Check for JoinColumn without ManyToOne or OneToOne
	if (preg_match('/#\[\s*(?:ORM\\\)?JoinColumn/', $content)) {
		$hasAssociation = false;
		foreach ($associationPatterns as $type => $pattern) {
			if ($type !== 'OneToMany' && $type !== 'ManyToMany') {
				if (preg_match($pattern, $content)) {
					$hasAssociation = true;
					break;
				}
			}
		}
		if (!$hasAssociation) {
			$errors[] = "#[JoinColumn] found without accompanying #[ManyToOne] or #[OneToOne] - JoinColumn only applies to single-valued associations";
		}
	}

	// Check for JoinTable without ManyToMany
	if (preg_match('/#\[\s*(?:ORM\\\)?JoinTable/', $content)) {
		if (!preg_match('/#\[\s*(?:ORM\\\)?ManyToMany/', $content)) {
			$errors[] = "#[JoinTable] found without #[ManyToMany] - JoinTable only applies to ManyToMany associations";
		}
	}

	// Check for OrderBy without OneToMany or ManyToMany
	if (preg_match('/#\[\s*(?:ORM\\\)?OrderBy/', $content)) {
		if (!preg_match('/#\[\s*(?:ORM\\\)?OneToMany|#\[\s*(?:ORM\\\)?ManyToMany/', $content)) {
			$errors[] = "#[OrderBy] found without #[OneToMany] or #[ManyToMany] - OrderBy only applies to to-many associations";
		}
	}

	return $errors;
}

/**
 * Validate PHP type compatibility with Doctrine types
 */
function validateTypeCompatibility(string $content, string $filePath): array
{
	$errors = [];

	// Map Doctrine types to compatible PHP types
	$typeMap = [
		'string' => ['string', '?string'],
		'text' => ['string', '?string'],
		'guid' => ['string', '?string'],
		'ascii_string' => ['string', '?string'],
		'integer' => ['int', '?int', 'integer', '?integer'],
		'smallint' => ['int', '?int'],
		'bigint' => ['string', '?string', 'int', '?int'],
		'boolean' => ['bool', '?bool', 'boolean', '?boolean'],
		'decimal' => ['string', '?string'],
		'float' => ['float', '?float', 'double', '?double'],
		'datetime' => ['DateTime', '?DateTime', '\\DateTime', '?\\DateTime'],
		'datetime_immutable' => ['DateTimeImmutable', '?DateTimeImmutable', '\\DateTimeImmutable', '?\\DateTimeImmutable'],
		'date' => ['DateTime', '?DateTime', '\\DateTime', '?\\DateTime'],
		'date_immutable' => ['DateTimeImmutable', '?DateTimeImmutable', '\\DateTimeImmutable', '?\\DateTimeImmutable'],
		'time' => ['DateTime', '?DateTime', '\\DateTime', '?\\DateTime'],
		'time_immutable' => ['DateTimeImmutable', '?DateTimeImmutable', '\\DateTimeImmutable', '?\\DateTimeImmutable'],
		'array' => ['array', '?array'],
		'simple_array' => ['array', '?array'],
		'json' => ['array', '?array'],
		'object' => ['object', '?array'],
	];

	// Extract properties with Column attributes and their types
	if (preg_match_all(
		'/private\s+(\??(?:\\\)?\w+)\s+\$(\w+).*?#\[\s*(?:ORM\\\)?Column\s*\(\s*([^)]*)\)\s*\]/s',
		$content,
		$matches,
		PREG_SET_ORDER
	)) {
		foreach ($matches as $match) {
			$phpType = $match[1];
			$propertyName = $match[2];
			$columnArgs = $match[3];

			// Extract Doctrine type
			if (preg_match('/type:\s*[\'"](\w+)[\'"]/', $columnArgs, $typeMatch)) {
				$doctrineType = strtolower($typeMatch[1]);

				// Check compatibility
				if (isset($typeMap[$doctrineType])) {
					$compatibleTypes = $typeMap[$doctrineType];
					$normalizedPhpType = ltrim($phpType, '\\');
					if (!in_array($phpType, $compatibleTypes, true)
					    && !in_array($normalizedPhpType, $compatibleTypes, true)) {
						$expected = implode(' or ', $compatibleTypes);
						$errors[] = "Property '$$propertyName' has PHP type '$phpType' but Doctrine type '$doctrineType' expects $expected";
					}
				}

				// Check for decimal without precision/scale
				if ($doctrineType === 'decimal') {
					if (!preg_match('/precision:/', $columnArgs) || !preg_match('/scale:/', $columnArgs)) {
						$errors[] = "Decimal column '$$propertyName' should specify precision and scale (e.g., precision: 10, scale: 2)";
					}
				}
			}
		}
	}

	// Check for DateTime property without proper import
	if (preg_match('/private\s+\??DateTime\s+\$/', $content)) {
		if (!preg_match('/use\s+DateTime;/', $content)
		    && !preg_match('/use\s+\\DateTime;/', $content)
		    && !preg_match('/private\s+\??\\DateTime/', $content)) {
			$errors[] = "DateTime property without proper namespace - use \\DateTime or add 'use DateTime;' import";
		}
	}

	// Check for Collection without proper initialization
	if (preg_match('/private\s+\w*Collection\s+\$(\w+)/', $content, $match)) {
		$propertyName = $match[1];
		// Check if initialized in constructor
		if (!preg_match('/new\s+ArrayCollection/', $content)
		    && !preg_match('/new\s+PersistentCollection/', $content)
		    && !preg_match('/this->' . $propertyName . '\s*=\s*\$/', $content)) {
			$errors[] = "Collection property '\${$propertyName}' should be initialized in constructor with new ArrayCollection()";
		}
	}

	return $errors;
}

/**
 * Extract class name from file content
 */
function extractClassName(string $content): ?string
{
	if (preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
		$namespace = $nsMatch[1];
		if (preg_match('/class\s+(\w+)/', $content, $classMatch)) {
			return $namespace . '\\' . $classMatch[1];
		}
	}
	return null;
}
