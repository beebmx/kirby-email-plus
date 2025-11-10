<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Contracts;

interface EmailPlusProviderContract
{
    public function withAttachments(): array;
}
