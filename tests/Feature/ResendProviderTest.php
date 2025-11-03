<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Resend\Client;

beforeEach(function () {
    $this->kirby = instance(
        options: ['type' => 'resend', 'resend' => ['key' => getenv('RESEND_SECRET')]]
    );
});

it('can send an email', function () {
    $mail = $this->kirby->email('resend', ['debug' => true]);

    expect($mail)
        ->send()
        ->toBeTrue();
});

it('can access to Resend\\Service\\Email object', function () {
    $this->kirby->email('resend', [
        'debug' => true,
        'beforeSend' => function (Client $mailer) {
            expect($mailer)
                ->toBeInstanceOf(Client::class);
        },
    ])->send();
});

it('can add attachments', function () {
    $site = $this->kirby->site();

    $mail = $this->kirby->email('resend', [
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
    instance(options: ['type' => 'resend']);

    App::instance()
        ->email('resend')
        ->send();
})->throws(InvalidArgumentException::class, '"beebmx.email-plus.resend.key" option should be set');

it('can set reset with email.transport.type', function () {
    App::destroy();
    instance(
        options: ['resend' => ['key' => getenv('RESEND_SECRET')]],
        email: ['transport' => ['type' => 'resend']],
    );

    App::instance()->email('resend', [
        'debug' => true,
        'beforeSend' => function (Client $mailer) {
            expect($mailer)
                ->toBeInstanceOf(Client::class);
        },
    ])->send();
});
