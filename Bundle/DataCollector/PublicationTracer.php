<?php
/**
 * This file is a part of the Event-Band project
 * @author Kirill chebba Chebunin
 * @author Vasil coylOne Kulakov <kulakov@vasiliy.pro>
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