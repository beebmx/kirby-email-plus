<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Providers;

use Beebmx\KirbEmailPlus\Concerns\HasSetupEmailOptions;
use Closure;
use Kirby\Cms\App;
use Kirby\Email\Email;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Toolkit\Str;
use Mailgun\Mailgun;
use Psr\Http\Client\ClientExceptionInterface;

final class MailgunProvider extends Email
{
    use HasSetupEmailOptions;

    protected bool $fake = true;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $props = [], bool $debug = false)
    {
        $this->hasDebugMode = $debug;
        $this->fake = $props['fake'] ?? false;

        parent::__construct($props, $debug);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    public function send(bool $debug = false): bool
    {
        if (empty(App::instance()->option('beebmx.email+.mailgun.key'))) {
            throw new InvalidArgumentException(
                message: '"beebmx.email+.mailgun.key" option should be set'
            );
        }

        $mailgun = Mailgun::create(
            App::instance()->option('beebmx.email+.mailgun.key'),
            App::instance()->option('beebmx.email+.mailgun.endpoint', 'https://api.mailgun.net')
        );

        $beforeSend = $this->beforeSend();

        if ($beforeSend instanceof Closure) {
            $client = $beforeSend->call($this, $mailgun) ?? $mailgun;

            if ($client instanceof Mailgun === false) {
                throw new InvalidArgumentException(
                    message: '"beforeSend" option return should be instance of Mailgun\Mailgun class'
                );
            }
        }

        if (! $this->fake && ($debug === true || $this->hasDebugMode === true)) {
            return $this->isSent = true;
        }

        $message = $mailgun->messages()
            ->send(
                domain: App::instance()->option('beebmx.email+.mailgun.domain'),
                params: array_merge(
                    $this->prepare(file: 'filePath', attachments: 'attachment', path: true),
                    ['o:testmode' => $this->fake === true ? 'yes' : 'no']
                )
            );

        return $this->isSent = Str::startsWith((string) $message->getStatusCode(), '20');
    }
}
