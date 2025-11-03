<?php

declare(strict_types=1);

namespace Beebmx\KirbEmailPlus\Providers;

use Beebmx\KirbEmailPlus\Concerns\HasSetupEmailOptions;
use Closure;
use Kirby\Cms\App;
use Kirby\Email\Email;
use Kirby\Exception\InvalidArgumentException;
use Resend;
use Resend\Client;

final class ResendProvider extends Email
{
    use HasSetupEmailOptions;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(array $props = [], bool $debug = false)
    {
        $this->hasDebugMode = $debug;

        parent::__construct($props, $debug);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function send(bool $debug = false): bool
    {
        if (empty(App::instance()->option('beebmx.email+.resend.key'))) {
            throw new InvalidArgumentException(
                message: '"beebmx.email+.resend.key" option should be set'
            );
        }

        $resend = Resend::client(
            App::instance()->option('beebmx.email+.resend.key')
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
            parameters: $this->prepare(file: 'content')
        );

        return $this->isSent = is_string($sent?->id) && ! is_null($sent?->id);
    }
}
