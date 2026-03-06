<?php
namespace Saitho\VersionDiffBundle\Utility;

use Carbon\Carbon;
use Pimcore\Cache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Version;
use Saitho\VersionDiffBundle\Model\DataObjectVersionDiff;
use Saitho\VersionDiffBundle\Model\VersionResult;

class DataObjectUtility
{
    /**
     * @var array<string, string[]> $ignorePropertiesInDiff
     */
    protected static array $ignorePropertiesInDiff = [
        DataObject\Concrete::class => [
            '__dataVersionTimestamp',
            'modificationDate',
            'versionCount'
        ],
        Carbon::class => [
            'constructedObjectId'
        ]
    ];

    /**
     * Ensure object relations use object ID in key, to avoid errors in array diff
     */
    protected static function prepareDataObjectForDiff(DataObject\Concrete $dataObject): DataObject\Concrete
    {
        try {
            $fieldDefinitions = $dataObject->getClass()->getFieldDefinitions();
            foreach ($fieldDefinitions as $fieldDefinition) {
                if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Relations\AbstractRelations) {
                    if (!$fieldDefinition->getName()) {
                        continue;
                    }
                    $newRelations = [];
                    /** @var DataObject\Data\ObjectMetadata $objectMetadata */
                    foreach ($dataObject->get($fieldDefinition->getName()) as $objectMetadata) {
                        $newRelations[$objectMetadata->getObjectId()] = $objectMetadata;
                    }
                    $dataObject->set($fieldDefinition->getName(), $newRelations);
                }
            }
        } catch (\Throwable) {
        }

        return $dataObject;
    }

    /**
     * @param DataObject\Concrete $dataObjectA
     * @param DataObject\Concrete $dataObjectB
     * @return DataObjectVersionDiff|null
     */
    public static function getDiff(DataObject\Concrete $dataObjectA, DataObject\Concrete $dataObjectB): DataObjectVersionDiff|null
    {
        return new DataObjectVersionDiff(
            self::prepareDataObjectForDiff($dataObjectA),
            self::prepareDataObjectForDiff($dataObjectB),
            DiffUtility::objDiff($dataObjectA, $dataObjectB, self::$ignorePropertiesInDiff)
        );
    }

    /**
     * @param DataObject\Concrete $dataObject
     * @param int $since
     * @return DataObjectVersionDiff|null
     */
    public static function getDiffSince(DataObject\Concrete $dataObject, int $since = 0): DataObjectVersionDiff|null
    {
        $versions = VersionUtility::getVersionsByCid([$dataObject->getId()], 'object')->getVersions();

        if (count($versions) === 1) {
            // only one version available
            return null;
        }

        $secondOldestVersion = null;
        do {
            if (isset($oldestVersion)) {
                $secondOldestVersion = $oldestVersion;
            }
            $oldestVersion = array_shift($versions);
        } while ($oldestVersion !== null && $oldestVersion->getDate() < $since);

        $latestVersion = array_pop($versions);
        if ($since > 0 && $latestVersion === false) {
            $latestVersion = $oldestVersion;
            $oldestVersion = $secondOldestVersion;
        }

        if (!$oldestVersion || !$latestVersion) {
            return null;
        }

        if (!$oldestVersion->getData() instanceof DataObject\Concrete || !$latestVersion->getData() instanceof DataObject\Concrete) {
            return null;
        }

        return self::getDiff($oldestVersion->getData(), $latestVersion->getData());
    }

    /**
     * @return DataObjectVersionDiff|null
     */
    public static function getDiffWithPrevious(Version $version): DataObjectVersionDiff|null
    {
        $previousVersion = VersionUtility::getPreviousVersion($version);
        if (!$previousVersion) {
            return null;
        }
        /** @var DataObject\Concrete $objectA */
        $objectA = $previousVersion->getData();
        /** @var Concrete $objectB */
        $objectB = $version->getData();
        if (!$objectA || !$objectB) {
            return null;
        }
        return self::getDiff($objectA, $objectB);
    }

    /**
     * @param string[] $additionalCacheTags
     * @return array<string, DataObjectVersionDiff[]>
     */
    public static function getDiffGroupedByDate(VersionResult $versions, bool $useCache = true, array $additionalCacheTags = [], bool $compareToPreviousDate = false): array
    {
        $versions = $versions->groupByDate();
        $dates = array_keys($versions);
        $cacheKey = sha1('versionDiffs-' . implode(',', $dates));
        /** @var array<string, DataObjectVersionDiff[]> $versionDiffs */
        $versionDiffs = Cache::load($cacheKey);
        if (!$versionDiffs || !$useCache) {
            /** @var array<string, DataObjectVersionDiff[]> $versionDiffs */
            $versionDiffs = [];
            foreach ($versions as $date => $versionArr) {
                $versionDiffs[$date] = array_filter(
                    array_map(
                        fn (Version $version) => self::getDiffWithPrevious($version),
                        $versionArr
                    )
                );
            }
            if ($useCache) {
                $cacheTags = ['versionDiff'];
                array_push($cacheTags, ...$additionalCacheTags);
                Cache::save($versionDiffs, $cacheKey, $cacheTags);
            }
        }
        return $versionDiffs;
    }
}
