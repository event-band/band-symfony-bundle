<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
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