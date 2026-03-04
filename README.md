# horde/version

Create, parse, increment, compare, and constrain strict SemVer V2 version tags and other common semantic versioning formats found in the wild.

## Features

- ✅ Parse strict SemVer 2.0 and relaxed variants
- ✅ Compare versions with full SemVer 2.0 precedence rules
- ✅ Generate next versions (patch/minor/major bumps)
- ✅ Detect and manage stability levels (dev/alpha/beta/rc/stable)
- ✅ Version constraints (Composer-compatible syntax)
- ✅ Collection utilities (sort, filter, find latest)
- ✅ Comprehensive test coverage (193 tests, 592 assertions)

## Installation

```bash
composer require horde/version
```

## Quick Start

### Parsing Versions

```php
use Horde\Version\SemVerV2Version;
use Horde\Version\RelaxedSemanticVersion;

// Strict SemVer 2.0
$version = new SemVerV2Version('1.0.0');
$version = new SemVerV2Version('1.0.0-alpha.1+build.123');

// Relaxed format (prefixes, no hyphen, missing patch)
$version = new RelaxedSemanticVersion('v1.0.0');
$version = new RelaxedSemanticVersion('1.0');           // → 1.0.0
$version = new RelaxedSemanticVersion('1.0.0alpha1');   // → 1.0.0-alpha1

// Access components
echo $version->major;        // 1
echo $version->minor;        // 0
echo $version->patch;        // 0
echo $version->preRelease;   // 'alpha1'
echo $version->buildInfo;    // ''
```

### Comparing Versions

```php
use Horde\Version\SemVerV2Comparison;

$comparison = new SemVerV2Comparison();
$result = $comparison->compare(
    new SemVerV2Version('1.0.0'),
    new SemVerV2Version('2.0.0')
); // -1 (first < second)

// Or use helper methods
$v1 = new SemVerV2Version('1.0.0');
$v2 = new SemVerV2Version('2.0.0');

$v1->isLessThan($v2);      // true
$v2->isGreaterThan($v1);   // true
$v1->equals($v1);          // true
```

### Version Constraints

```php
use Horde\Version\ConstraintParser;

$parser = new ConstraintParser();

// Exact match
$constraint = $parser->parse('1.0.0');
$constraint->isSatisfiedBy($version); // true if version == 1.0.0

// Comparison operators
$constraint = $parser->parse('>=1.0.0');
$constraint = $parser->parse('<2.0.0');
$constraint = $parser->parse('!=1.5.0');

// Caret (^) - Allow changes that don't modify left-most non-zero
$constraint = $parser->parse('^1.2.3');  // >=1.2.3 <2.0.0
$constraint = $parser->parse('^0.2.3');  // >=0.2.3 <0.3.0
$constraint = $parser->parse('^0.0.3');  // =0.0.3

// Tilde (~) - Allow patch-level changes
$constraint = $parser->parse('~1.2.3');  // >=1.2.3 <1.3.0
$constraint = $parser->parse('~1.2');    // >=1.2.0 <2.0.0

// Wildcard
$constraint = $parser->parse('1.0.*');   // >=1.0.0 <1.1.0
$constraint = $parser->parse('1.*');     // >=1.0.0 <2.0.0

// Range
$constraint = $parser->parse('1.0.0 - 2.0.0');

// AND (space-separated)
$constraint = $parser->parse('>=1.0.0 <2.0.0');

// OR (||)
$constraint = $parser->parse('^1.0 || ^2.0');

// Check if version satisfies constraint
if ($constraint->isSatisfiedBy($version)) {
    echo "Version is compatible!";
}
```

### Collection Utilities

```php
use Horde\Version\VersionCollection;

$versions = ['2.0.0', '1.0.0', '1.5.0', '2.0.0-alpha'];

// Sort versions
$sorted = VersionCollection::sort($versions);
// ['1.0.0', '1.5.0', '2.0.0-alpha', '2.0.0']

$sortedDesc = VersionCollection::sort($versions, descending: true);
// ['2.0.0', '2.0.0-alpha', '1.5.0', '1.0.0']

// Find latest version
$latest = VersionCollection::latest($versions);
// '2.0.0'

$latestStable = VersionCollection::latest($versions, 'stable');
// '2.0.0' (excludes alpha)

// Filter by constraint
$compatible = VersionCollection::filter($versions, '^1.0');
// ['1.0.0', '1.5.0']

// Filter by stability
$stable = VersionCollection::filter($versions, 'stable');
// ['1.0.0', '1.5.0', '2.0.0']

// Filter by callback
$filtered = VersionCollection::filter($versions, fn($v) => $v->major >= 2);
// ['2.0.0', '2.0.0-alpha']

// Group by major version
$grouped = VersionCollection::group($versions, 'major');
// ['1.x' => ['1.0.0', '1.5.0'], '2.x' => ['2.0.0', '2.0.0-alpha']]
```

### Generating Next Versions

```php
use Horde\Version\NextVersion;

$current = new NextVersion('1.0.0');

// Bump patch version
$next = $current('patch', 'stable');  // '1.0.1'

// Bump minor version
$next = $current('minor', 'stable');  // '1.1.0'

// Bump major version
$next = $current('major', 'stable');  // '2.0.0'

// Change stability
$next = $current('minor', 'alpha');   // '1.1.0-alpha.1'
$next = $current('patch', 'beta');    // '1.0.1-beta.1'

// Keep same stability
$next = $current('patch', 'unchanged'); // '1.0.1'

// Increment prerelease revision
$current = new NextVersion('1.0.0-alpha.1');
$next = $current('patch', 'unchanged'); // '1.0.0-alpha.2'
```

### Stability Detection

```php
use Horde\Version\Stability;

$version = new SemVerV2Version('1.0.0-beta.2');
$stability = new Stability($version);

echo $stability->stability;           // 'beta'
echo $stability->stabilityRank;       // 2 (dev=0, alpha=1, beta=2, rc=3, stable=1000)

$stability->isStable();               // false
$stability->isBeta();                 // true
$stability->getStabilityRevision();   // 2
```

## Motivation

SemVer V2 is a useful attempt to standardize semantic versioning. However, semantic versioning itself predates it, and many variant formats exist in the wild.

### Common SemVer Deviations

- **Stability order**: Treating "dev" as inferior to "alpha" (SemVer says alpha < beta < dev < rc)
- **No hyphen**: Prerelease without hyphen (`1.0.0alpha1` instead of `1.0.0-alpha.1`)
- **Missing patch**: Version strings with only major.minor (`1.0` instead of `1.0.0`)
- **Prefixes**: `v` prefix before version (`v1.0.0`)
- **Prerelease format**: `alpha4` more common than `alpha.4`

This library handles both strict SemVer 2.0 and these common variants.

## Use Cases

### Dependency Management
```php
// Check if installed version satisfies requirement
$parser = new ConstraintParser();
$required = $parser->parse('^1.2.0');
$installed = new SemVerV2Version('1.5.0');

if ($required->isSatisfiedBy($installed)) {
    echo "Dependency satisfied!";
}
```

### Update Checker
```php
// Find latest compatible version
$availableVersions = ['1.0.0', '1.5.0', '2.0.0', '2.1.0'];
$currentVersion = '1.3.0';

// Find latest in same major version
$compatible = VersionCollection::filter($availableVersions, '^' . $currentVersion);
$latestCompatible = VersionCollection::latest($compatible);
// '1.5.0'
```

### Release Management
```php
// Find all stable versions in 1.x series
$allVersions = ['1.0.0', '1.5.0', '2.0.0-alpha', '2.0.0'];
$v1Stable = VersionCollection::filter($allVersions, function($v) {
    return $v->major === 1 && (new Stability($v))->isStable();
});
// ['1.0.0', '1.5.0']
```

## API Documentation

### Core Classes

- **`SemVerV2Version`** - Strict SemVer 2.0.0 parser
- **`RelaxedSemanticVersion`** - Lenient parser for common variants
- **`GenericVersion`** - Any version string
- **`SemVerV2Comparison`** - Compare versions per SemVer rules
- **`NextVersion`** - Generate next version with bumps
- **`Stability`** - Detect and classify stability levels

### Constraint System

- **`VersionConstraint`** - Interface for all constraints
- **`ConstraintParser`** - Parse constraint strings
- **`ExactConstraint`** - Exact version match
- **`ComparisonConstraint`** - `>`, `>=`, `<`, `<=`, `!=`
- **`CaretConstraint`** - `^1.2.3` ranges
- **`TildeConstraint`** - `~1.2.3` ranges
- **`WildcardConstraint`** - `1.0.*` patterns
- **`CompositeConstraint`** - AND/OR logic

### Utilities

- **`VersionCollection`** - Sort, filter, find versions
- **`VersionComparable`** - Helper methods trait

## Requirements

- PHP 8.2 or higher
- No external dependencies

## Testing

```bash
composer install
vendor/bin/phpunit
```

Test coverage: 193 tests, 592 assertions

## License

LGPL-2.1-only - See LICENSE file

## Contributing

This library is part of the Horde project. Contributions welcome!

## Origin

Originally extracted from the horde/components developer CLI tool, rewritten for general-purpose use.
