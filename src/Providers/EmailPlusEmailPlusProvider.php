<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Providers;

use Beebmx\KirbEmailPlus\Concerns\HasSetupEmailOptions;
use Beebmx\KirbEmailPlus\Contracts\EmailPlusProviderContract;
use Kirby\Email\Email;
use Kirby\Exception\InvalidArgumentException;

abstract class EmailPlusEmailPlusProvider extends Email implements EmailPlusProviderContract
{
    use HasSetupEmailOptions;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $props = [], bool $debug = false)
    {
        $this->hasDebugMode = $debug;
        $this->fake = $props['fake'] ?? false;

        parent::__construct($props, $debug);
    }
}
