<?php

use Beebmx\KirbEmailPlus\Providers\MailgunProvider;
use Beebmx\KirbEmailPlus\Providers\ResendProvider;
use Kirby\Cms\App;
use Kirby\Email\PHPMailer;

it('return kirby email provider', function () {
    $kirby = instance();

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf(PHPMailer::class);
});

it('return resend email provider', function () {
    $kirby = instance(options: ['type' => 'resend']);

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf(ResendProvider::class);
});

it('return mailgun email provider', function () {
    $kirby = instance(options: ['type' => 'mailgun']);

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf(MailgunProvider::class);
});

afterEach(function () {
    App::destroy();
});
