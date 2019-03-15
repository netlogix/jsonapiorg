<?php
namespace Netlogix\JsonApiOrg\Schema\Traits;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use TYPO3\Flow\Utility\Arrays;

/**
 * Sparse fields are optional resource fields thant can be either
 * added or skipped when exposing data.
 * This is valid for both, attributes and relationships.
 * @see http://jsonapi.org/format/#fetching-sparse-fieldsets
 */
trait SparseFieldsTrait
{

    /**
     * @var array
     */
    protected $sparseFields = array('*');

    /**
     * @param mixed $sparseFields
     */
    public function setSparseFields($sparseFields)
    {
        if (is_array($sparseFields)) {
            $sparseFields = join(',', $sparseFields);
        }
        $this->sparseFields = array();
        if (is_string($sparseFields)) {
            foreach (Arrays::trimExplode(',', $sparseFields) as $sparseField) {
                if (!$sparseField) {
                    continue;
                }
                $this->sparseFields[$sparseField] = $sparseField;
            }
        }
    }

    /**
     * @param string $fieldName
     * @return boolean
     */
    public function isAllowedSparseField($fieldName)
    {
        if (in_array('*', $this->sparseFields)) {
            return true;
        } else {
            return in_array($fieldName, $this->sparseFields);
        }
    }

}