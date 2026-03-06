<?php
namespace Saitho\VersionDiffBundle\Utility;

use Pimcore\Model\Version;
use Saitho\VersionDiffBundle\Model\VersionResult;

class VersionUtility
{
    /**
     * @param (int|null)[] $cidList
     * @param string $cType
     * @param int $limit
     * @param "asc"|"desc" $order
     * @param int $since
     * @return VersionResult
     */
    public static function getVersionsByCid(
        array $cidList,
        string $cType,
        int $limit = 0,
        string $order = 'desc',
        int $since = 0
    ): VersionResult {
        $list = new Version\Listing();
        $list->setCondition('ctype = ?', $cType);
        $list->addConditionParam('cid IN (' . implode(',', array_filter($cidList)) .')');
        $list->setOrderKey('date');
        $list->setOrder($order);
        if ($limit > 0) {
            $list->setLimit($limit);
        }
        if ($since > 0) {
            $list->addConditionParam('date > ?', $since);
        }

        return new VersionResult($list->getData() ?? []);
    }

    public static function getPreviousVersion(Version $version): Version|null
    {
        if ($version->getVersionCount() === 1) {
            return null;
        }
        $previousVersion = (new Version\Listing())
            ->setCondition('ctype = ?', $version->getCtype())
            ->addConditionParam('cid = ?', $version->getCid())
            ->addConditionParam('versionCount < ?', $version->getVersionCount())
            ->setOrderKey('versionCount')
            ->setOrder('DESC')
            ->setLimit(1)
            ->getData();
        if ($previousVersion === null) {
            return null;
        }
        return array_shift($previousVersion);
    }
}
