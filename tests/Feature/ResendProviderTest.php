<?php

use Kirby\Cms\App;
use Kirby\Exception\InvalidArgumentException;
use Resend\Client;
use Resend\Exceptions\ErrorException;
use Tests\Fakes\FakeHttpClient;

beforeEach(function () {
    $this->kirby = instance(
        options: ['type' => 'resend', 'resend' => ['key' => testEnv('RESEND_SECRET', 're_test')]]
    );
});

it('can send an email', function () {
    $mail = $this->kirby->email('resend', ['debug' => true]);

    expect($mail)
        ->send()
        ->toBeTrue();
});

it('can access to Resend\\Client object', function () {
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
        options: ['resend' => ['key' => testEnv('RESEND_SECRET', 're_test')]],
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

describe('sending through a mocked API', function () {
    beforeEach(function () {
        $this->http = new FakeHttpClient;
    });

    it('sends the email to the Resend API', function () {
        $this->http->respondWith(200, ['id' => '49a3999c-0ce1-4ea6-ab68-afcd6dc2e794']);

        $mail = $this->kirby->email('resend', [
            'beforeSend' => returning(fakeResend($this->http)),
        ]);

        expect($mail->isSent())->toBeTrue()
            ->and($this->http->requests())->toHaveCount(1);

        $request = $this->http->lastRequest();

        expect($request->getMethod())->toBe('POST')
            ->and((string) $request->getUri())->toBe('https://api.resend.com/emails')
            ->and($request->getHeaderLine('Authorization'))->toBe('Bearer re_test')
            ->and($this->http->json())->toBe([
                'from' => 'Some name <someone@notifications.beeb.mx>',
                'to' => ['John Doe <delivered+john@resend.dev>', 'delivered+jane@resend.dev'],
                'subject' => 'Thank you for your testing request',
                'html' => 'This is just a test',
                'text' => 'This is just a test',
                'reply_to' => 'no-reply@notifications.beeb.mx',
                'cc' => ['delivered+other@resend.dev'],
            ]);
    });

    it('sends the html and text body', function () {
        $this->http->respondWith(200, ['id' => 'email-id']);

        $this->kirby->email('resend', [
            'body' => ['html' => '<p>Hello</p>', 'text' => 'Hello'],
            'bcc' => 'secret@resend.dev',
            'beforeSend' => returning(fakeResend($this->http)),
        ]);

        expect($this->http->json())
            ->html->toBe('<p>Hello</p>')
            ->text->toBe('Hello')
            ->bcc->toBe(['secret@resend.dev']);
    });

    it('sends the attachments encoded in base64', function () {
        $this->http->respondWith(200, ['id' => 'email-id']);
        $site = $this->kirby->site();

        $this->kirby->email('resend', [
            'attachments' => [$site->file('beeb.png'), $site->file('empty.pdf')],
            'beforeSend' => returning(fakeResend($this->http)),
        ]);

        expect($this->http->json()['attachments'])->toBe([
            ['content' => base64_encode(file_get_contents(fixtures('site/content/beeb.png'))), 'filename' => 'beeb.png'],
            ['content' => base64_encode(file_get_contents(fixtures('site/content/empty.pdf'))), 'filename' => 'empty.pdf'],
        ]);
    });

    it('does not call the API in debug mode', function () {
        $mail = $this->kirby->email('resend', [
            'debug' => true,
            'beforeSend' => returning(fakeResend($this->http)),
        ]);

        expect($mail->send())->toBeTrue()
            ->and($this->http->requests())->toBeEmpty();
    });

    it('throws when the Resend API rejects the email', function () {
        $this->http->respondWith(422, [
            'statusCode' => 422,
            'name' => 'validation_error',
            'message' => 'Invalid `from` field.',
        ]);

        $this->kirby->email('resend', [
            'beforeSend' => returning(fakeResend($this->http)),
        ]);
    })->throws(ErrorException::class, 'Invalid `from` field.');

    it('throws when beforeSend returns another client', function () {
        $this->kirby->email('resend', [
            'debug' => true,
            'beforeSend' => fn () => new stdClass,
        ])->send();
    })->throws(InvalidArgumentException::class, '"beforeSend" option return should be instance of Resend\Client class');
});
