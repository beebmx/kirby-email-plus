<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus;

use Beebmx\KirbEmailPlus\Providers\MailgunProvider;
use Beebmx\KirbEmailPlus\Providers\ResendProvider;
use Kirby\Cms\App;
use Kirby\Email\Email;
use Kirby\Email\PHPMailer;
use Kirby\Exception\InvalidArgumentException;

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
    public function __invoke(array $props = [], bool $debug = false): Email|MailgunProvider|ResendProvider
    {
        return match ($this->getTransport()) {
            'mailgun' => new MailgunProvider($props, $debug),
            'resend' => new ResendProvider($props, $debug),
            default => new PHPMailer($props, $debug),
        };
    }

    private function getTransport(): string
    {
        return ! empty($this->kirby->option('beebmx.email+.type'))
            ? $this->kirby->option('beebmx.email+.type')
            : $this->kirby->option('email.transport.type');
    }
}
