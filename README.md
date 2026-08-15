# Pimcore VersionDiffBundle

A small Pimcore bundle that compares Pimcore `Version` snapshots and builds structured diffs for DataObjects.

**Note:** This README file was generated using AI. The code was done by a human.

## Requirements

- PHP 8.3+
- Pimcore `>=12.0 <13.0 || ^2026.1.6`

## Installation

```bash
composer require saitho/pimcore-versiondiff-bundle
```

The bundle class can be enabled in `config/bundles.php` or via the Pimcore extension manager, but this is **optional** — the utility classes are usable as soon as Composer autoloads the package.

## What it does

The bundle provides utilities to:

- Load Pimcore versions by element ID / ctype.
- Find the previous version of a given `Version`.
- Build recursive diffs between two arbitrary objects or arrays.
- Build Pimcore DataObject-aware diffs that ignore version metadata and normalize object relations.
- Group version diffs by date and cache the result.

## Main components

| Class | Purpose |
|-------|---------|
| `VersionDiffBundle` | Empty Pimcore bundle registration class. |
| `Utility\VersionUtility` | Fetch versions and resolve the previous version of a `Pimcore\Model\Version`. |
| `Utility\DiffUtility` | Recursively diff arrays / objects into an array of changed values. |
| `Utility\DataObjectUtility` | Pimcore DataObject-specific diff helpers. |
| `Model\VersionResult` | Wrapper around a list of `Version` instances with grouping helpers. |
| `Model\VersionDiff` | Generic diff result: old data, new data, diff array. |
| `Model\DataObjectVersionDiff` | VersionDiff for `DataObject\Concrete` with `wasPublished()` / `wasUnpublished()` helpers. |

## Usage examples

### Diff a DataObject against its previous version

```php
use Pimcore\Model\Version;
use Saitho\VersionDiffBundle\Utility\DataObjectUtility;

/** @var Version $version */
$diff = DataObjectUtility::getDiffWithPrevious($version);

if ($diff) {
    $changedFields = $diff->getDiff();
    $wasPublished = $diff->wasPublished();
}
```

### Diff a DataObject against the oldest version since a given timestamp

```php
use Saitho\VersionDiffBundle\Utility\DataObjectUtility;

$diff = DataObjectUtility::getDiffSince($dataObject, strtotime('-7 days'));
```

### Load versions for one or more elements

```php
use Saitho\VersionDiffBundle\Utility\VersionUtility;

$result = VersionUtility::getVersionsByCid([1, 2, 3], 'object', limit: 50);
$versions = $result->getVersions();
$byDate = $result->groupByDate();
$byCid = $result->groupByCid();
```

### Build diffs grouped by date and cache them

```php
use Saitho\VersionDiffBundle\Utility\DataObjectUtility;

$diffsByDate = DataObjectUtility::getDiffGroupedByDate(
    $versionResult,
    useCache: true,
    additionalCacheTags: ['my-custom-tag'],
);
```

## Diff format

`VersionDiff::getDiff()` returns an associative array. For each changed key the value is an array with two entries:

```php
[
    'fieldName' => [oldValue, newValue],
]
```

Nested objects and arrays are diffed recursively. Added keys show `null` as the old value, removed keys show `null` as the new value.

## DataObject-specific behavior

`DataObjectUtility::getDiff()` performs a few Pimcore-specific normalizations before diffing:

- Ignores internal version fields: `__dataVersionTimestamp`, `modificationDate`, `versionCount`.
- Ignores Carbon's `constructedObjectId`.
- Re-keys object relations by the related object's ID so array diffs stay stable.

## Caching

`DataObjectUtility::getDiffGroupedByDate()` writes results to Pimcore's `Cache` under the tag `versionDiff`. Pass additional cache tags if you want to invalidate the cache together with other application data.

## License

This bundle is licensed under the Pimcore Open Core License (POCL). See [LICENSE.md](LICENSE.md).
