<?php

use Beebmx\KirbEmailPlus\Providers\MailgunEmailPlusProvider;
use Beebmx\KirbEmailPlus\Providers\ResendEmailPlusProvider;
use Composer\Semver\Semver;
use Kirby\Cms\App;
use Kirby\Email\PHPMailer;
use Kirby\Plugin\Plugin;

beforeEach(function () {
    // App::destroy() clears the plugin registry, so the plugin is registered before every test
    require dirname(__DIR__, 2).'/index.php';
});

function pluginInstance(array $options = []): App
{
    App::$enableWhoops = false;

    return new App([
        'roots' => [
            'index' => '/dev/null',
            'base' => $base = dirname(__DIR__).'/Fixtures',
            'site' => $site = $base.'/site',
            'content' => $site.'/content',
        ],
        'options' => array_merge([
            'email' => [
                'presets' => [
                    'test' => [
                        'from' => 'someone@example.com',
                        'to' => 'john@doe.co',
                        'subject' => 'Testing the plugin',
                        'body' => 'This is just a test',
                    ],
                ],
            ],
        ], $options),
    ]);
}

afterEach(function () {
    App::destroy();
});

it('registers the plugin', function () {
    expect(App::plugin('beebmx/email-plus'))
        ->toBeInstanceOf(Plugin::class)
        ->name()->toBe('beebmx/email-plus');
});

it('declares a Kirby compatibility constraint that matches the installed Kirby version', function () {
    $composer = json_decode(file_get_contents(dirname(__DIR__, 2).'/composer.json'), true);
    $compatible = $composer['extra']['kirby']['compatible'] ?? null;

    expect($compatible)
        ->toBeString()
        ->and(Semver::satisfies(App::version(), $compatible))
        ->toBeTrue();
});

it('registers the default options', function () {
    $kirby = pluginInstance();

    expect($kirby->option('beebmx.email-plus.type'))->toBeNull()
        ->and($kirby->option('beebmx.email-plus.mailgun.endpoint'))->toBe('https://api.mailgun.net')
        ->and($kirby->option('beebmx.email-plus.mailgun.domain'))->toBeNull()
        ->and($kirby->option('beebmx.email-plus.mailgun.key'))->toBeNull()
        ->and($kirby->option('beebmx.email-plus.resend.key'))->toBeNull();
});

it('uses the Kirby email provider through the plugin component by default', function () {
    expect(pluginInstance()->email('test', ['debug' => true]))
        ->toBeInstanceOf(PHPMailer::class);
});

it('uses the provider set in the plugin options through the plugin component', function (string $type, string $provider) {
    $kirby = pluginInstance(['beebmx.email-plus' => ['type' => $type]]);

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf($provider);
})->with([
    'resend' => ['resend', ResendEmailPlusProvider::class],
    'mailgun' => ['mailgun', MailgunEmailPlusProvider::class],
]);

it('uses the provider set in the email transport through the plugin component', function (string $type, string $provider) {
    $kirby = pluginInstance(['email' => [
        'transport' => ['type' => $type],
        'presets' => [
            'test' => [
                'from' => 'someone@example.com',
                'to' => 'john@doe.co',
                'subject' => 'Testing the plugin',
                'body' => 'This is just a test',
            ],
        ],
    ]]);

    expect($kirby->email('test', ['debug' => true]))
        ->toBeInstanceOf($provider);
})->with([
    'resend' => ['resend', ResendEmailPlusProvider::class],
    'mailgun' => ['mailgun', MailgunEmailPlusProvider::class],
]);
