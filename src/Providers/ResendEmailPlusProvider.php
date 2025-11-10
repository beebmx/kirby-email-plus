<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Providers;

use Closure;
use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\F;
use Resend;
use Resend\Client;

final class ResendEmailPlusProvider extends EmailPlusEmailPlusProvider
{
    /**
     * @throws InvalidArgumentException
     */
    public function send(bool $debug = false): bool
    {
        if (empty(App::instance()->option('beebmx.email-plus.resend.key'))) {
            throw new InvalidArgumentException(
                message: '"beebmx.email-plus.resend.key" option should be set'
            );
        }

        $resend = Resend::client(
            App::instance()->option('beebmx.email-plus.resend.key')
        );

        $beforeSend = $this->beforeSend();

        if ($beforeSend instanceof Closure) {
            $client = $beforeSend->call($this, $resend) ?? $resend;

            if ($client instanceof Client === false) {
                throw new InvalidArgumentException(
                    message: '"beforeSend" option return should be instance of Resend\Client class'
                );
            }
        }

        if ($debug === true || $this->hasDebugMode === true) {
            return $this->isSent = true;
        }

        $sent = $resend->emails->send(
            parameters: $this->prepare(),
        );

        return $this->isSent = is_string($sent?->id) && ! is_null($sent?->id);
    }

    public function withAttachments(): array
    {
        if (! count($this->attachments())) {
            return [];
        }

        return ['attachments' => $this->mapAttatchments($this->attachments())];
    }

    protected function mapAttatchments(array $attachments): array
    {
        return array_map(
            fn ($attachment) => is_string($attachment)
                ? [
                    'content' => F::base64($attachment),
                    'filename' => F::filename($attachment),
                ] : $attachment,
            $attachments
        );
    }
}
