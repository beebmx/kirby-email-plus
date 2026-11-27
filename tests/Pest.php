<?php

use Beebmx\KirbEmailPlus\TargetEmailProvider;
use Kirby\Cms\App;
use Mailgun\HttpClient\HttpClientConfigurator;
use Mailgun\Mailgun;
use Resend\Client as ResendClient;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;
use Tests\Fakes\FakeHttpClient;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function instance(array $roots = [], array $options = [], array $email = [], array $hooks = []): App
{
    App::$enableWhoops = false;

    return new App([
        'roots' => array_merge([
            'index' => '/dev/null',
            'base' => $base = dirname(__DIR__).'/tests/Fixtures',
            'site' => $site = $base.'/site',
            'content' => $site.'/content',
        ], $roots),
        'components' => [
            'email' => fn ($kirby, $props, $debug) => (new TargetEmailProvider)($props, $debug),
        ],
        'options' => [
            'email' => array_merge([
                'presets' => [
                    'test' => [
                        'from' => 'someone@example.com',
                        'fromName' => 'Some name',
                        'replyTo' => 'no-reply@example.com',
                        'to' => ['john@doe.co' => 'John Doe', 'jane@doe.co'],
                        'subject' => 'Thank you for your testing request',
                        'cc' => 'other@doe.co',
                        'body' => 'This is just a test',
                    ],
                    'resend' => [
                        'from' => 'someone@notifications.beeb.mx',
                        'fromName' => 'Some name',
                        'replyTo' => 'no-reply@notifications.beeb.mx',
                        'to' => ['delivered+john@resend.dev' => 'John Doe', 'delivered+jane@resend.dev'],
                        'subject' => 'Thank you for your testing request',
                        'cc' => 'delivered+other@resend.dev',
                        'body' => 'This is just a test',
                    ],
                    'mailgun' => [
                        'from' => 'no-reply@m.beeb.mx',
                        'fromName' => 'Some name',
                        'replyTo' => 'no-reply@m.beeb.mx',
                        'to' => ['john@doe.co' => 'John Doe', 'jane@doe.co'],
                        'subject' => 'Thank you for your testing request',
                        'cc' => 'other@doe.co',
                        'body' => 'This is just a test',
                    ],
                ],
                'transport' => [
                    'type' => 'smtp',
                ],
            ], $email),
            'hooks' => $hooks,
            'beebmx.email-plus' => array_merge(
                require dirname(__DIR__).'/extensions/options.php',
                $options
            ),
        ],
    ]);
}

function fixtures(string $path): string
{
    return dirname(__DIR__).'/tests/Fixtures/'.$path;
}

/**
 * A real Resend client whose HTTP calls go to the fake client
 */
function fakeResend(FakeHttpClient $http): ResendClient
{
    return new ResendClient(new HttpTransporter(
        $http,
        BaseUri::from('api.resend.com'),
        Headers::withAuthorization(ApiKey::from('re_test'))
    ));
}

/**
 * A real Mailgun client whose HTTP calls go to the fake client
 */
function fakeMailgun(FakeHttpClient $http): Mailgun
{
    return new Mailgun(
        (new HttpClientConfigurator)
            ->setApiKey('mg_test')
            ->setEndpoint('https://api.mailgun.net')
            ->setHttpClient($http)
    );
}

/**
 * A "beforeSend" callback that swaps the SDK client for the given one.
 * Kirby binds the callback to the email, so the client is captured here.
 */
function returning(object $client): Closure
{
    return fn () => $client;
}

/**
 * Reads an environment variable (see phpunit.dist.xml) with a dummy fallback,
 * so the tests run without API keys. The tests never send a real email.
 */
function testEnv(string $key, string $default): string
{
    return getenv($key) ?: $default;
}
