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
 * Include fields means naming individual relationship paths hand
 * having even nested relationships added to the TopLevel object.
 *
 * @see http://jsonapi.org/format/#fetching-includes
 */
trait IncludeFieldsTrait
{

    /**
     * @var array
     */
    protected $includeFields = array();

    /**
     * @param mixed $includeFields
     */
    public function setIncludeFields($includeFields)
    {
        if (is_array($includeFields)) {
            $includeFields = join(',', $includeFields);
        }
        $this->includeFields = array();
        if (is_string($includeFields)) {
            foreach (Arrays::trimExplode(',', $includeFields) as $includeField) {
                if (!$includeField) {
                    continue;
                }
                $this->includeFields[$includeField] = $includeField;
            }
        }
    }

    /**
     * @param string $fieldName
     * @return mixed
     */
    public function isAllowedIncludeField($fieldName)
    {
        if (in_array('*', $this->includeFields)) {
            return true;
        } else {
            return in_array($fieldName, $this->includeFields);
        }
    }

}