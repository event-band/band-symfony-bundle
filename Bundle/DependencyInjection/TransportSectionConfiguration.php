<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
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