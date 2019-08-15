<?php

namespace Netlogix\JsonApiOrg\Property\TypeConverter\Entity;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Property\Exception as PropertyException;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfigurationInterface;

class PersistentObjectFromTopLevelArrayConverter extends PersistentObjectConverter
{
    const TARGET_TYPE = '__target_type';

    protected $priority = 1565610123;

    protected $sourceTypes = ['array'];

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * Only convert non-persistent types
     *
     * @param mixed $source
     * @param string $targetType
     * @return boolean
     */
    public function canConvertFrom($source, $targetType)
    {
        if (
            !is_array($source)
            || !array_key_exists('data', $source)
        ) {
            return false;
        }
        foreach ($this->getFlatCandidatesArrayFromSource($source) as $candidate) {
            if (!$this->canConvertSingleObject($candidate)) {
                return false;
            }
        }
        return true;
    }

    public function convertFrom(
        $source,
        $targetType,
        array $convertedChildProperties = [],
        PropertyMappingConfigurationInterface $configuration = null
    ) {
        $scoped = function (
            $source,
            $targetType,
            array $convertedChildProperties = [],
            PropertyMappingConfigurationInterface $configuration = null
        ) {
            $subject = $this->getSubjectFromSource($source);

            $included = $this->getIncludedFromSource($source);
            $included = $this->addTargetTypeToIncluded($included);
            // FIXME: Calcuate actual dependency graph here
            $included = array_reverse($included);

            $this->mapIncluded($included);

            $target = $this->propertyMapper->convert($subject, $targetType, $configuration);
            BatchScope::instance()->addObject($subject, $target);

            return $target;
        };
        return BatchScope::wrap($scoped, $source, $targetType, $convertedChildProperties, $configuration);
    }

    protected function mapIncluded(array $included)
    {
        while ($included !== []) {
            $countBefore = count($included);
            $included = array_filter($included, function (array $candidate) {
                try {
                    $subject = $this->propertyMapper->convert($candidate, $candidate[self::TARGET_TYPE]);
                    BatchScope::instance()->addObject($candidate, $subject);
                    return false;
                } catch (PropertyException $e) {
                    return true;
                }
            });
            $countAfter = count($included);
            if ($countAfter === $countBefore) {
                return false;
            }
        }
        return true;
    }

    protected function getFlatCandidatesArrayFromSource(array $source)
    {
        return array_merge(
            [$this->getSubjectFromSource($source)],
            $this->getIncludedFromSource($source)
        );
    }

    protected function getSubjectFromSource(array $source): array
    {
        return $source['data'];
    }

    protected function getIncludedFromSource(array $source): array
    {
        return $source['included'] ?? [];
    }

    protected function getNumberOfDependencies(array $data): int
    {
        return isset($data['relationships']) ? count($data['relationships']) : 0;
    }

    protected function addTargetTypeToIncluded(array $included)
    {
        return array_map(function (array $candidate) {
            $targetType = $this->exposableTypeMap->getClassName($candidate['type']);
            $candidate[self::TARGET_TYPE] = $targetType;
            return $candidate;
        }, $included);
    }
}