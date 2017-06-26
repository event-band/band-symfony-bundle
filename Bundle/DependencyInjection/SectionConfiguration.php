<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Class SectionConfiguration
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
interface SectionConfiguration 
{
    /**
     * @return ArrayNodeDefinition
     */
    public function getSectionDefinition();
}