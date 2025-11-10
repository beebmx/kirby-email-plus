<?php

use Beebmx\KirbEmailPlus\Providers\MailgunEmailPlusProvider;
use Beebmx\KirbEmailPlus\Providers\ResendEmailPlusProvider;
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
        ->toBeInstanceOf(ResendEmailPlusProvider::class);
});

it('return mailgun email provider', function () {
    $kirby = instance(options: ['type' => 'mailgun']);

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf(MailgunEmailPlusProvider::class);
});

afterEach(function () {
    App::destroy();
});
