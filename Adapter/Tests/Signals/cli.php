<?php

$loader = include __DIR__ . '/../../../vendor/autoload.php';

$cli = new \Symfony\Component\Console\Application();
$cli->add(new \EventBand\Adapter\Symfony\Tests\Signals\CommandStub());

$cli->run();
