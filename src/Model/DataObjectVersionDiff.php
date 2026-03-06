<?php
namespace Saitho\VersionDiffBundle\Model;

use Pimcore\Model\DataObject\Concrete;

/**
 * @extends VersionDiff<Concrete>
 */
class DataObjectVersionDiff extends VersionDiff
{
    public function wasPublished(): bool
    {
        return !($this->diff['published'][0] ?? false) && ($this->diff['published'][1] ?? false);
    }

    public function wasUnpublished(): bool
    {
        return !($this->diff['published'][0] ?? false) && ($this->diff['published'][1] ?? false);
    }
}
