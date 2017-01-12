<?php
namespace Netlogix\JsonApiOrg\Domain\Model;

/*
 * This file is part of the Netlogix.JsonApiOrg package.
 */

use TYPO3\Flow\Mvc\Routing\UriBuilder;

interface LinksAwareModelInterface
{
    /**
     * Build all necessary links to expose to the client.
     *
     * @param UriBuilder $uriBuilder
     * @return string|array
     */
    public function buildLinks(UriBuilder $uriBuilder);
}
