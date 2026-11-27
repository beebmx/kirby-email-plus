<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Mailgun\Exception\HttpClientException;
use Mailgun\Mailgun;
use Tests\Fakes\FakeHttpClient;

beforeEach(function () {
    $this->kirby = instance(
        options: ['type' => 'mailgun', 'mailgun' => [
            'domain' => testEnv('MAILGUN_DOMAIN', 'example.com'),
            'endpoint' => testEnv('MAILGUN_ENDPOINT', 'https://api.mailgun.net'),
            'key' => testEnv('MAILGUN_SECRET', 'mg_test'),
        ]]
    );
});

it('can send an email', function () {
    $mail = $this->kirby->email('mailgun', ['debug' => true, 'fake' => false]);

    expect($mail)
        ->send()
        ->toBeTrue();
});

it('can access to Mailgun\\Mailgun object', function () {
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
        'debug' => true, 'fake' => false,
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
            'domain' => testEnv('MAILGUN_DOMAIN', 'example.com'),
            'endpoint' => testEnv('MAILGUN_ENDPOINT', 'https://api.mailgun.net'),
            'key' => testEnv('MAILGUN_SECRET', 'mg_test'),
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

describe('sending through a mocked API', function () {
    beforeEach(function () {
        $this->http = new FakeHttpClient;
    });

    it('sends the email to the Mailgun API', function () {
        $this->http->respondWith(200, ['id' => '<20261127.1@m.beeb.mx>', 'message' => 'Queued. Thank you.']);

        $mail = $this->kirby->email('mailgun', [
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);

        expect($mail->isSent())->toBeTrue()
            ->and($this->http->requests())->toHaveCount(1);

        $request = $this->http->lastRequest();

        expect($request->getMethod())->toBe('POST')
            ->and((string) $request->getUri())->toBe('https://api.mailgun.net/v3/'.testEnv('MAILGUN_DOMAIN', 'example.com').'/messages')
            ->and($request->getHeaderLine('Authorization'))->toBe('Basic '.base64_encode('api:mg_test'))
            ->and($this->http->multipart())->toBe([
                'from' => ['Some name <no-reply@m.beeb.mx>'],
                'to' => ['John Doe <john@doe.co>', 'jane@doe.co'],
                'subject' => ['Thank you for your testing request'],
                'html' => ['This is just a test'],
                'text' => ['This is just a test'],
                'reply_to' => ['no-reply@m.beeb.mx'],
                'cc' => ['other@doe.co'],
                'o:testmode' => ['no'],
            ]);
    });

    it('sends the html and text body', function () {
        $this->http->respondWith(200, ['id' => '<id@m.beeb.mx>', 'message' => 'Queued. Thank you.']);

        $this->kirby->email('mailgun', [
            'body' => ['html' => '<p>Hello</p>', 'text' => 'Hello'],
            'bcc' => 'secret@doe.co',
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);

        expect($this->http->multipart())
            ->html->toBe(['<p>Hello</p>'])
            ->text->toBe(['Hello'])
            ->bcc->toBe(['secret@doe.co']);
    });

    it('sends the attachments as files', function () {
        $this->http->respondWith(200, ['id' => '<id@m.beeb.mx>', 'message' => 'Queued. Thank you.']);
        $site = $this->kirby->site();

        $this->kirby->email('mailgun', [
            'attachments' => [$site->file('beeb.png'), $site->file('empty.pdf')],
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);

        expect($this->http->multipart()['attachment'])->toBe([
            ['filename' => 'beeb.png', 'content' => file_get_contents(fixtures('site/content/beeb.png'))],
            ['filename' => 'empty.pdf', 'content' => file_get_contents(fixtures('site/content/empty.pdf'))],
        ]);
    });

    it('enables the Mailgun test mode with the fake option', function () {
        $this->http->respondWith(200, ['id' => '<id@m.beeb.mx>', 'message' => 'Queued. Thank you.']);

        $mail = $this->kirby->email('mailgun', [
            'fake' => true,
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);

        expect($mail->isSent())->toBeTrue()
            ->and($this->http->multipart()['o:testmode'])->toBe(['yes']);
    });

    it('does not call the API in debug mode', function () {
        $mail = $this->kirby->email('mailgun', [
            'debug' => true,
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);

        expect($mail->send())->toBeTrue()
            ->and($this->http->requests())->toBeEmpty();
    });

    it('throws when the Mailgun API rejects the email', function () {
        $this->http->respondWith(401, ['message' => 'Invalid private key']);

        $this->kirby->email('mailgun', [
            'beforeSend' => returning(fakeMailgun($this->http)),
        ]);
    })->throws(HttpClientException::class);

    it('throws when beforeSend returns another client', function () {
        $this->kirby->email('mailgun', [
            'debug' => true,
            'beforeSend' => fn () => new stdClass,
        ])->send();
    })->throws(InvalidArgumentException::class, '"beforeSend" option return should be instance of Mailgun\Mailgun class');
});
