<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;

/**
 * Class TransportSectionConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
interface TransportSectionConfiguration extends SectionConfiguration
{
    /**
     * @return NodeDefinition
     */
    public function getPublisherNode();

    /**
     * @return NodeDefinition
     */
    public function getConsumerNode();
}