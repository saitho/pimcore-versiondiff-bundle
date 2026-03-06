<?php
namespace Saitho\VersionDiffBundle\Utility;

class DiffUtility
{
    /**
     * @param mixed[]|object $data
     * @param array<string, string[]> $propertiesToIgnore
     * @param int[] $processedObjects
     * @return array<int|string, mixed>
     */
    public static function objectToArray(array|object $data, array $propertiesToIgnore = [], array &$processedObjects = []): array
    {
        if (is_array($data)) {
            return array_map(
                function (mixed $innerData) use ($propertiesToIgnore, $processedObjects) {
                    if (is_array($innerData)) {
                        return self::objectToArray($innerData, $propertiesToIgnore, $processedObjects);
                    }
                    if (!is_object($innerData)) {
                        return $innerData;
                    }
                    return self::singleObjectToArray($innerData, $propertiesToIgnore, $processedObjects);
                },
                $data
            );
        }
        return self::singleObjectToArray($data, $propertiesToIgnore, $processedObjects);
    }

    /**
     * @param object $data
     * @param array<string, string[]> $propertiesToIgnore
     * @param int[] $processedObjects
     * @return array<string, mixed>
     */
    protected static function singleObjectToArray(object $data, array $propertiesToIgnore = [], array &$processedObjects = []): array
    {
        $result = [];
        if (in_array(spl_object_id($data), $processedObjects)) {
            return $result;
        }
        $processedObjects[] = spl_object_id($data);
        $reflection = new \ReflectionClass($data);
        foreach ($reflection->getProperties() as $property) {
            if (!$property->isInitialized($data)) {
                continue;
            }
            $key = $property->getName();
            if (in_array($key, $propertiesToIgnore[$reflection->getName()] ?? []) ||
                ($reflection->getParentClass() && in_array($key, $propertiesToIgnore[$reflection->getParentClass()->getName()] ?? []))
            ) {
                continue;
            }
            $value = $property->getValue($data);
            $result[$key] = (is_array($value) || is_object($value)) ?
                self::objectToArray($value, $propertiesToIgnore, $processedObjects) : $value;
        }
        return $result;
    }

    /**
     * @param object $obj1
     * @param object $obj2
     * @param array<string, string[]> $propertiesToIgnore
     * @return array<int|string, mixed>
     */
    public static function objDiff(object $obj1, object $obj2, array $propertiesToIgnore = []): array
    {
        $a1 = self::objectToArray($obj1, $propertiesToIgnore);
        $a2 = self::objectToArray($obj2, $propertiesToIgnore);
        return self::arrDiff($a1, $a2);
    }

    /**
     * @param array<int|string, mixed> $a1
     * @param array<int|string, mixed> $a2
     * @return array<int|string, mixed>
     */
    public static function arrDiff(array $a1, array $a2): array
    {
        $r = [];
        foreach ($a1 as $k => $v) {
            if (array_key_exists($k, $a2)) {
                if (is_object($v) && is_object($a2[$k])) {
                    $rad = self::objDiff($v, $a2[$k]);
                    if (count($rad)) {
                        $r[$k] = $rad;
                    }
                } else if (is_array($v) && is_array($a2[$k])) {
                    $rad = self::arrDiff($v, $a2[$k]);
                    if (count($rad)) {
                        $r[$k] = $rad;
                    }
                } else if (is_double($v) && is_double($a2[$k])) {
                    // required to avoid rounding errors due to the
                    // conversion from string representation to double
                    if (abs($v - $a2[$k]) > 0.000000000001) {
                        $r[$k] = array($v, $a2[$k]);
                    }
                } else {
                    if ($v != $a2[$k]) {
                        $r[$k] = array($v, $a2[$k]);
                    }
                }
            } else {
                $r[$k] = array($v, null);
            }
        }

        // check keys not in a1
        foreach ($a2 as $key => $value) {
            if (array_key_exists($key, $a1)) {
                continue;
            }
            $r[$key] = array(null, $value);
        }

        return $r;
    }
}
