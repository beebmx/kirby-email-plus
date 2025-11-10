<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus;

use Beebmx\KirbEmailPlus\Providers\EmailPlusEmailPlusProvider;
use Kirby\Cms\App;
use Kirby\Email\Email;
use Kirby\Email\PHPMailer;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Str;

final class TargetEmailProvider
{
    protected App $kirby;

    public function __construct()
    {
        $this->kirby = App::instance();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(array $props = [], bool $debug = false): Email|EmailPlusEmailPlusProvider
    {
        $provider = 'Beebmx\\KirbEmailPlus\\Providers\\'.Str::studly($this->getTransport()).'EmailPlusProvider';

        if (class_exists($provider)) {
            return new $provider($props, $debug);
        }

        return new PHPMailer($props, $debug);
    }

    private function getTransport(): string
    {
        return ! empty($this->kirby->option('beebmx.email-plus.type'))
            ? $this->kirby->option('beebmx.email-plus.type')
            : $this->kirby->option('email.transport.type');
    }
}
