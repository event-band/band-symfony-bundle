<?php
/**
 * @LICENSE_TEXT
 */

namespace EventBand\Bundle\DataCollector;

/**
 * Publication
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
interface PublicationTracer
{
    /**
     * @return AmqpPublicationEntry[]
     */
    public function getLoggedPublications();
}