<?php

arch()->preset()->php();

arch()->preset()->security();

arch()->preset()->strict();

arch('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
