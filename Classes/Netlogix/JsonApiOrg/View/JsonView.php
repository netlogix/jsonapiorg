<?php
namespace Netlogix\JsonApiOrg\View;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Psr\Http\Message\ResponseInterface;

/**
 * The regular JsonView adds the Content-Type to the result.
 * That's basically the only thing this view does, apart from
 * calling json_encode on all schema elements.
 *
 * @deprecated with Flow 9.0 please use the native json_encode instead, without relying on the flow object conversion magic
 * @see \Neos\Flow\Mvc\View\JsonView
 */
class JsonView extends \Neos\Flow\Mvc\View\JsonView
{

    /**
     * Supported options
     * @var array
     */
    protected $supportedOptions = array(
        'jsonEncodingOptions' => array(
            JSON_PRETTY_PRINT,
            'Bitmask of supported Encoding options. See http://php.net/manual/en/json.constants.php',
            'integer'
        ),
        'contentTypeHeader' => array('application/json', 'The Content-Type to be exposed', 'string'),
    );

    /**
     * Transforms the value view variable to a serializable
     * array represantion using a YAML view configuration and JSON encodes
     * the result.
     *
     * @return string The JSON encoded variables
     * @api
     */
    public function render(): ResponseInterface
    {
        $response = parent::render();
        $this->controllerContext->getResponse()->setContentType($this->getOption('contentTypeHeader'));

        return $response;
    }

    /**
     * Transforms a value depending on type recursively using the
     * supplied configuration.
     *
     * @param mixed $value The value to transform
     * @param array $configuration Configuration for transforming the value
     * @return array The transformed value
     */
    protected function transformValue($value, array $configuration)
    {
        return json_decode(json_encode($value));
    }

}