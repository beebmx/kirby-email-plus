<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Providers;

use Closure;
use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Str;
use Mailgun\Mailgun;
use Psr\Http\Client\ClientExceptionInterface;

final class MailgunEmailPlusProvider extends EmailPlusEmailPlusProvider
{
    /**
     * @throws ClientExceptionInterface
     * @throws InvalidArgumentException
     */
    public function send(bool $debug = false): bool
    {
        if (empty(App::instance()->option('beebmx.email-plus.mailgun.key'))) {
            throw new InvalidArgumentException(
                message: '"beebmx.email-plus.mailgun.key" option should be set'
            );
        }

        $mailgun = Mailgun::create(
            App::instance()->option('beebmx.email-plus.mailgun.key'),
            App::instance()->option('beebmx.email-plus.mailgun.endpoint', 'https://api.mailgun.net')
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
                domain: App::instance()->option('beebmx.email-plus.mailgun.domain'),
                params: array_merge(
                    $this->prepare(),
                    ['o:testmode' => $this->fake === true ? 'yes' : 'no']
                )
            );

        return $this->isSent = Str::startsWith((string) $message->getStatusCode(), '20');
    }

    public function withAttachments(): array
    {
        if (! count($this->attachments())) {
            return [];
        }

        return ['attachment' => $this->mapAttatchments($this->attachments())];
    }

    protected function mapAttatchments(array $attachments): array
    {
        return array_map(
            fn ($attachment) => is_string($attachment)
                ? [
                    'filePath' => $attachment,
                    'filename' => F::filename($attachment),
                ] : $attachment,
            $attachments
        );
    }
}
