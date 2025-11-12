<p align="center">
<a href="https://github.com/beebmx/kirby-email-plus/actions"><img src="https://img.shields.io/github/actions/workflow/status/beebmx/kirby-email-plus/tests.yml?branch=main" alt="Build Status"></a>
<a href="https://packagist.org/packages/beebmx/kirby-email-plus"><img src="https://img.shields.io/packagist/dt/beebmx/kirby-email-plus" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/beebmx/kirby-email-plus"><img src="https://img.shields.io/packagist/v/beebmx/kirby-email-plus" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/beebmx/kirby-email-plus"><img src="https://img.shields.io/packagist/l/beebmx/kirby-email-plus" alt="License"></a>
</p>

# Email+ for Kirby

Kirby has a built-in [email](https://getkirby.com/docs/reference/objects/cms/app/email) engine with support for `mail` and `smtp` transports.
However, sometimes you may need more support for other email services like [Mailgun](https://www.mailgun.com) or [Resend](https://resend.com).
Kirby `Email+` extends Kirby's email capabilities by adding support for multiple email services using the same Kirby email API.

![Email+](/.github/assets/banner.jpg)

****

## Overview

- [1. Installation](#installation)
- [2. Usage](#usage)
- [3. Options](#options)
- [4. License](#license)
- [5. Credits](#credits)

## Installation

Right now, `Email+` supports two additional email transports: `mailgun` and `resend`.

### Mailgun installation

To use Mailgun as your email transport, you need to run the following command to install the required packages:

```
composer require beebmx/kirby-email-plus mailgun/mailgun-php symfony/http-client nyholm/psr7
```

### Resend installation

To use Resend as your email transport, you need to run the following command to install the required packages:

```
composer require beebmx/kirby-email-plus resend/resend-php
```

## Usage

With Kirby `Email+` you can choose between the built-in [Kirby email](https://getkirby.com/docs/guide/emails) transports (`mail` and `smtp`) and the new ones added by this plugin (`mailgun` and `resend`).

First you need to configure the email transport you want to use in your `config.php` file:

```php
return [
    'beebmx.email-plus' => [
        'type' => 'resend',
        'resend.key' => 're_XXXXXXXXXXXXXXXX',
    ],
];
```

Then, you can send emails using the Kirby email API as usual:

```php
$kirby->email([
    'from'    => 'welcome@supercompany.com',
    'replyTo' => 'no-reply@supercompany.com',
    'to'      => 'someone@gmail.com',
    'cc'      => 'anotherone@gmail.com',
    'bcc'     => 'secret@gmail.com',
    'subject' => 'Welcome!',
    'body'    => 'It\'s great to have you with us',
  ]);
```

For convinience, you can also set the `type` directly in the transport configuration:

```php
return [
    'email' => [
        'transport' => [
            'type' => 'resend',
        ],
    ],
    'beebmx.email-plus.resend.key' => 're_XXXXXXXXXXXXXXXX',
];
````

## Options

| Option                             |   Type   |         Default         | Description                                      |
|:-----------------------------------|:--------:|:-----------------------:|:-------------------------------------------------|
| beebmx.email-plus.type             | `string` |          null           | Define the email transport `mailgun` or `resend` |
| beebmx.email-plus.mailgun.domain   | `string` |          null           | Define your `mailgun` domain to send emails.     |
| beebmx.email-plus.mailgun.endpoint | `string` | https://api.mailgun.net | `https://api.eu.mailgun.net` for EU servers.     |
| beebmx.email-plus.mailgun.key      | `string` |          null           | Define your API Key for `mailgun`                |
| beebmx.email-plus.resend.key       | `string` |          null           | Define your API Key for `resend`                 |

Here's an example of a full use of the options from the `config.php` file:

```php
return [
  'beebmx.email-plus' => [
      'type' => 'resend', // mailgun or resend
      'mailgun' => [
          'domain' => 'example.com',
          'endpoint' => 'https://api.mailgun.net',
          'key' => 'key-XXXXXXXXXXXXXXXX',
      ],
      'resend' => [
          'key' => 're_XXXXXXXXXXXXXXXX',
      ],
  ],
];
```

## License

Licensed under the [MIT](LICENSE.md).

## Credits

- Fernando Gutierrez [@beebmx](https://github.com/beebmx)
- [All Contributors](../../contributors)
