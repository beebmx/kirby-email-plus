<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Concerns;

use Kirby\Toolkit\A;

trait HasSetupEmailOptions
{
    protected bool $hasDebugMode = false;

    protected bool $fake = false;

    protected function prepare(): array
    {
        return array_merge([
            'from' => $this->withForm(),
            'to' => $this->withTo(),
            'subject' => $this->subject(),
            'html' => $this->getHtml(),
            'text' => $this->getText(),
        ],
            $this->withReply(),
            $this->withCc(),
            $this->withBcc(),
            $this->withAttachments()
        );
    }

    protected function withForm(): string
    {
        return $this->parseEmail($this->from(), $this->fromName() ?? '');
    }

    protected function withReply(): array
    {
        return ! empty($this->replyTo())
            ? ['reply_to' => $this->parseEmail($this->replyTo(), $this->replyToName() ?? '')]
            : [];
    }

    protected function withTo(): array
    {
        return $this->mapEmails('to');
    }

    protected function withCc(): array
    {
        $emails = $this->mapEmails('cc');

        return count($emails)
            ? ['cc' => $emails]
            : [];
    }

    protected function withBcc(): array
    {
        $emails = $this->mapEmails('bcc');

        return count($emails)
            ? ['bcc' => $emails]
            : [];
    }

    protected function getHtml(): string
    {
        return $this->isHtml()
            ? $this->body()->html()
            : $this->body()->text();
    }

    protected function getText(): string
    {
        return $this->body()->text();
    }

    protected function mapEmails(string $target): array
    {
        $emails = A::wrap($this->{$target}());
        $keys = array_keys($emails);

        return array_map(
            fn ($value, $key) => $this->parseEmail($key, $value ?? ''), $emails, $keys
        );
    }

    protected function parseEmail(string $email, ?string $name = null): string
    {
        return $name ? "$name <$email>" : $email;
    }
}
