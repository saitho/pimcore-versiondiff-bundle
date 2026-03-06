<?php
namespace Saitho\VersionDiffBundle\Model;

/**
 * @template T
 *
 */
class VersionDiff
{
    /**
     * @param T $oldestData
     * @param T $latestData
     * @param array<int|string, mixed> $diff
     */
    public function __construct(protected mixed $oldestData, protected mixed $latestData, protected array $diff)
    {
    }

    /**
     * @return T
     */
    public function getOldestData(): mixed
    {
        return $this->oldestData;
    }

    /**
     * @return T
     */
    public function getLatestData(): mixed
    {
        return $this->latestData;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getDiff(): array
    {
        return $this->diff;
    }
}
