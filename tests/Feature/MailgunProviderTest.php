<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Mailgun\Mailgun;

beforeEach(function () {
    $this->kirby = instance(
        options: ['type' => 'mailgun', 'mailgun' => [
            'domain' => getenv('MAILGUN_DOMAIN'),
            'endpoint' => getenv('MAILGUN_ENDPOINT'),
            'key' => getenv('MAILGUN_SECRET'),
        ]]
    );
});

it('can send an email', function () {
    $mail = $this->kirby->email('mailgun', ['debug' => true, 'fake' => true]);

    expect($mail)
        ->send()
        ->toBeTrue();
});

it('can access to Resend\\Service\\Email object', function () {
    $this->kirby->email('mailgun', [
        'debug' => true,
        'beforeSend' => function ($mailer) {
            expect($mailer)
                ->toBeInstanceOf(Mailgun::class);
        },
    ])->send();
});

it('can add attachments', function () {
    $site = $this->kirby->site();

    $mail = $this->kirby->email('mailgun', [
        'debug' => true,
        'attachments' => [
            $site->file('beeb.png'),
            $site->file('empty.jpg'),
            $site->file('empty.pdf'),
        ],
    ]);

    expect($mail)
        ->send()
        ->toBeTrue();
});

it('throws an error if no key is set', function () {
    App::destroy();
    instance(options: ['type' => 'mailgun']);

    App::instance()
        ->email('mailgun')
        ->send();
})->throws(InvalidArgumentException::class, '"beebmx.email-plus.mailgun.key" option should be set');

it('can set reset with email.transport.type', function () {
    App::destroy();
    instance(
        options: ['type' => 'mailgun', 'mailgun' => [
            'domain' => getenv('MAILGUN_DOMAIN'),
            'endpoint' => getenv('MAILGUN_ENDPOINT'),
            'key' => getenv('MAILGUN_SECRET'),
        ]],
        email: ['transport' => ['type' => 'mailgun']],
    );

    App::instance()->email('mailgun', [
        'debug' => true,
        'beforeSend' => function ($mailer) {
            expect($mailer)
                ->toBeInstanceOf(Mailgun::class);
        },
    ])->send();
});
