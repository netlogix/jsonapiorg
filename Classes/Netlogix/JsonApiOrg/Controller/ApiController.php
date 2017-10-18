<?php
namespace Netlogix\JsonApiOrg\Controller;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Netlogix\JsonApiOrg\Resource\Resolver\ResourceResolverBySubrequest;
use Netlogix\JsonApiOrg\View\JsonView;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\RestController;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\PropertyMappingConfiguration;
use Neos\Flow\Property\TypeConverter\MediaTypeConverterInterface;
use Neos\Utility\Arrays;

/**
 * An action controller dealing with jsonapi.org data structures.
 *
 * @Flow\Scope("singleton")
 */
abstract class ApiController extends RestController
{

    /**
     * @var array
     */
    protected $supportedMediaTypes = array(
        'application/vnd.api+json',
        'application/json',
        'text/html'
    );

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'json' => JsonView::class
    );

    /**
     * @var \Netlogix\JsonApiOrg\Resource\Information\ResourceMapper
     * @Flow\Inject
     */
    protected $resourceMapper;

    /**
     * @var \Netlogix\JsonApiOrg\Resource\RelationshipIterator
     * @Flow\Inject
     */
    protected $relationshipIterator;

    /**
     * According to jsonapi.org specifications, every relationship must be
     * presented by its very own URI and available for both, reading and
     * writing.
     * This value determines the argument name of the showRelationshipAction,
     * createRelationshipAction and deleteRelationshipAction pointing to
     * the field name of the desired relationship.
     *
     * @var string
     */
    protected $relationshipArgumentName = 'relationshipName';

    /**
     * Determines the action method and assures that the method exists.
     *
     * @return string The action method name
     * @throws \Neos\Flow\Mvc\Exception\NoSuchActionException if the action specified in the request object does not exist (and if there's no default action either).
     */
    protected function resolveActionMethodName()
    {
        $this->request->__previousControllerActionName = $this->request->getControllerActionName();

        if ($this->request->__previousControllerActionName === 'index' && $this->request->getHttpRequest()->getHeader(ResourceResolverBySubrequest::SUB_REQUEST_HEADER) == 'true') {
            $this->request->setControllerActionName('showUnwrapped');
        }

        if ($this->request->getControllerActionName() === 'index') {
            $actionName = 'index';
            switch ($this->request->getHttpRequest()->getMethod()) {
                case 'HEAD':
                case 'GET' :
                    $actionName = ($this->request->hasArgument($this->resourceArgumentName)) ? 'show' : 'list';
                    break;
                case 'POST' :
                    $actionName = 'create';
                    break;
                case 'PUT' :
                    if (!$this->request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
                    }
                    $actionName = 'update';
                    break;
                case 'DELETE' :
                    if (!$this->request->hasArgument($this->resourceArgumentName)) {
                        $this->throwStatus(400, null, 'No resource specified');
                    }
                    $actionName = 'delete';
                    break;
            }
            if ($this->request->hasArgument($this->relationshipArgumentName) && $actionName !== 'list') {
                $actionName .= 'Relationship';
            }
            $this->request->setControllerActionName($actionName);
        }

        return parent::resolveActionMethodName();
    }

    /**
     * The content of the root request is used as resource argument.
     *
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\InvalidArgumentTypeException
     * @see initializeArguments()
     */
    protected function initializeActionMethodArguments()
    {
        if ($this->request->__previousControllerActionName === 'index') {
            switch ($this->request->getHttpRequest()->getMethod()) {
                case 'POST':
                case 'PUT':
                    $arguments = $this->request->getArguments();
                    if (!isset($arguments[$this->resourceArgumentName])) {
                        $arguments[$this->resourceArgumentName] = [];
                    }
                    $arguments[$this->resourceArgumentName] = array_merge_recursive($arguments[$this->resourceArgumentName],
                        $this->extractRequestBody());
                    $this->request->setArguments($arguments);
                    break;
            }
        }
        parent::initializeActionMethodArguments();
    }

    /**
     * The content of the HTTP request is provided as json in a
     * jsonapi.org structure.
     *
     * @return mixed
     * @throws \Neos\Flow\Http\Exception
     */
    protected function extractRequestBody()
    {
        $propertyMappingConfiguration = new PropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverter($this->objectManager->get(MediaTypeConverterInterface::class));
        $propertyMappingConfiguration->setTypeConverterOption(MediaTypeConverterInterface::class,
            MediaTypeConverterInterface::CONFIGURATION_MEDIA_TYPE, 'application/json');
        $result = $this->objectManager->get(PropertyMapper::class)->convert($this->request->getHttpRequest()->getContent(),
            'array', $propertyMappingConfiguration);

        return $result['data'];
    }

    /**
     *
     */
    public function initializeAction()
    {
        $this->relationshipIterator->setSupportedMediaTypes($this->supportedMediaTypes);

        if ($this->request->hasArgument('include')) {
            $include = Arrays::trimExplode(',', $this->request->getArgument('include'));
            $this->relationshipIterator->setInclude($include);
        }

        if ($this->request->hasArgument('fields')) {
            $fields = array();
            foreach ((array)$this->request->getArgument('fields') as $typeName => $fieldsList) {
                $fields[$typeName] = Arrays::trimExplode(',', $fieldsList);
            }
            $this->relationshipIterator->setFields($fields);
        }
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * Override this method to solve assign variables common for all actions
     * or prepare the view in another way before the action is called.
     *
     * @param ViewInterface $view The view to be initialized
     * @return void
     * @api
     */
    protected function initializeView(ViewInterface $view)
    {
        parent::initializeView($view);

        if (!is_null($this->view) && $this->view instanceof JsonView && $this->request->getFormat() === 'json') {

            $this->view->setOption('contentTypeHeader', current($this->supportedMediaTypes));
        }
    }

    protected function errorAction()
    {
        $validationResults = $this->arguments->getValidationResults()->getFlattenedErrors();
        $result = ['errors' => []];

        $pattern = '%^' . preg_quote($this->resourceArgumentName, '%') . '\\.?%';
        /** @var \Neos\Error\Messages\Error $validationResult */
        foreach ($validationResults as $key => $validationResult) {
            if (preg_match($pattern, $key)) {
                $key = preg_replace($pattern, '', $key);
                $key = rtrim('/data/' . str_replace('.', '/', $key), '/');
                $source = ['pointer' => $key];
            } else {
                $key = '/' . str_replace('.', '/', $key);
                $source = ['parameter' => $key];
            }

            /** @var \Neos\Flow\Validation\Error $error */
            foreach ($validationResult as $error) {
                $result['errors'][] = array(
                    'source' => $source,
                    'code' => $error->getCode(),
                    'title' => $error->render()
                );
            }
        }
        $this->response->setStatus(400);
        $this->response->setHeader('Content-Type', current($this->supportedMediaTypes));

        return json_encode($result, JSON_PRETTY_PRINT);
    }


}