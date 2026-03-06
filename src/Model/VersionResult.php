<?php
namespace Saitho\VersionDiffBundle\Model;

use Pimcore\Model\Version;

class VersionResult
{
    /**
     * @param Version[] $versions
     */
    public function __construct(protected array $versions)
    {
    }

    /**
     * @return Version[]
     */
    public function getVersions(): array
    {
        return $this->versions;
    }

    /**
     * @return array<int, Version[]>
     */
    public function groupByCid(): array
    {
        $groupedList = [];

        foreach ($this->versions as $datum) {
            if (!array_key_exists($datum->getCid(), $groupedList)) {
                $groupedList[$datum->getCid()] = [];
            }
            $groupedList[$datum->getCid()][] = $datum;
        }

        return $groupedList;
    }

    /**
     * @return array<string, Version[]>
     */
    public function groupByDate(): array
    {
        $groupedList = [];

        foreach ($this->versions as $datum) {
            $key = date('Y-m-d', $datum->getDate());
            if (!array_key_exists($key, $groupedList)) {
                $groupedList[$key] = [];
            }
            $groupedList[$key][] = $datum;
        }

        return $groupedList;
    }
}
