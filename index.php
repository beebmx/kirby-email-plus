<?php

use Beebmx\KirbEmailPlus\TargetEmailProvider;
use Kirby\Cms\App as Kirby;

@include_once __DIR__.'/vendor/autoload.php';

Kirby::plugin('beebmx/email-plus', [
    'components' => [
        'email' => fn ($kirby, $props, $debug) => (new TargetEmailProvider)($props, $debug),
    ],
    'options' => require_once __DIR__.'/extensions/options.php',
]);
