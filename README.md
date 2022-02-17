Charcoal Communicator
=====================

[![License][badge-license]][charcoal-contrib-communicator]
[![Latest Stable Version][badge-version]][charcoal-contrib-communicator]
[![Code Quality][badge-scrutinizer]][dev-scrutinizer]
[![Coverage Status][badge-coveralls]][dev-coveralls]
[![Build Status][badge-travis]][dev-travis]

A [Charcoal][charcoal-app] service for easy email template presets.



## Table of Contents

-   [Installation](#installation)
    -   [Dependencies](#dependencies)
-   [Service Provider](#service-provider)
    -   [Parameters](#parameters)
    -   [Services](#services)
-   [Configuration](#configuration)
-   [Usage](#usage)
-   [Development](#development)
    -  [API Documentation](#api-documentation)
    -  [Development Dependencies](#development-dependencies)
    -  [Coding Style](#coding-style)
-   [Credits](#credits)
-   [License](#license)



## Installation

The preferred (and only supported) method is with Composer:

```shell
$ composer require locomotivemtl/charcoal-contrib-communicator
```



### Dependencies

#### Required

-   [**PHP**](https://php.net): 7.3+
-   [**locomotivemtl/charcoal-email**](https://packagist.org/packages/locomotivemtl/charcoal-email): ~0.6



## Service Provider

### Services

- **communicator**: Instance of `Charcoal\Communicator\Communicator`.



## Configuration

The Communicator uses _scenarios_ (such as a enquiry confirmation) grouped into
_channels_ (such as for a user or an administrator). These can be defined from
the application configset:

```json
{
    "communicator": {
        "user": {
            "contact": {
                "log": true,
                "campaign": "",
                "subject": "Contact Us Confirmation",
                "template_ident": "communicator/email/default",
                "template_data": {
                    "message": "Thank you {{ form_data.full_name }} for your interest in our company! We received your request for information and will contact you as soon as we can."
                }
            }
        },
        "admin": {
            "contact": {
                "log": true,
                "campaign": "",
                "subject": "Contact Us Notification",
                "template_ident": "communicator/email/default",
                "template_data": {
                    "title": "New Contact form submission from {{ form_data.full_name }}",
                    "message": "{{ form_data.full_name }} would like information concerning {{ form_data.category }}.",
                    "charcoal": "<a href=\"{{ template_data.charcoal_url }}\"><b>See the entry on Charcoal<b></a>"
                }
            }
        }
    }
}
```

See [`communicator.sample.json`](blob/master/config/communicator.sample.json)
for a thorough example.



## Usage

The Communicator can prepare and send emails based on the selected scenario,
channel, and any custom data:

```php
/**
 * @var \Charcoal\Communicator\Communicator $communicator
 * @var \App\Model\Contact\Entry            $entry
 */

$formData = [
    'full_name'     => $entry['full_name'],
    'business_name' => $entry['business_name'],
    'email_address' => $entry['email_address'],
    'category'      => transform($entry['category_id'], function ($categoryId) {
        // Fetch name of Category object from Category ID.
    }),
    'message'       => $entry['message'],
];

$communicator->setFormData($formData);

$communicator->setTo([
    'email' => $entry['email_address'],
    'name'  => $entry['full_name'],
]);

$emailData = [
    'template_data' => [
        'entry'        => $formData,
        'charcoal_url' => build_admin_url('object/edit', [
            'obj_type' => Entry::objType(),
            'obj_id'   => $entry['id'],
        ]),
    ],
];

/** @var bool */
$sent = $communicator->send('contact', 'user', $emailData);
```

By default, the Communicator will use the email address from `email.default_from`
from your application configset.



## Development

To install the development environment:

```shell
$ composer install
```

To run the scripts (phplint, phpcs, and phpunit):

```shell
$ composer test
```



### API Documentation

-   The auto-generated `phpDocumentor` API documentation is available at:  
    [https://locomotivemtl.github.io/charcoal-contrib-communicator/docs/master/](https://locomotivemtl.github.io/charcoal-contrib-communicator/docs/master/)
-   The auto-generated `apigen` API documentation is available at:  
    [https://codedoc.pub/locomotivemtl/charcoal-contrib-communicator/master/](https://codedoc.pub/locomotivemtl/charcoal-contrib-communicator/master/index.html)



### Development Dependencies

-   [php-coveralls/php-coveralls](https://packagist.org/packages/php-coveralls/php-coveralls)
-   [phpunit/phpunit](https://packagist.org/packages/phpunit/phpunit)
-   [squizlabs/php_codesniffer](https://packagist.org/packages/squizlabs/php_codesniffer)



### Coding Style

The charcoal-contrib-communicator module follows the Charcoal coding-style:

-   [_PSR-1_][psr-1]
-   [_PSR-2_][psr-2]
-   [_PSR-4_][psr-4], autoloading is therefore provided by _Composer_.
-   [_phpDocumentor_](http://phpdoc.org/) comments.
-   [phpcs.xml.dist](phpcs.xml.dist) and [.editorconfig](.editorconfig) for coding standards.

> Coding style validation / enforcement can be performed with `composer phpcs`. An auto-fixer is also available with `composer phpcbf`.



## Credits

-   [Locomotive](https://locomotive.ca/)



## License

Charcoal is licensed under the MIT license. See [LICENSE](LICENSE) for details.



[charcoal-contrib-communicator]:  https://packagist.org/packages/locomotivemtl/charcoal-contrib-communicator
[charcoal-app]:             https://packagist.org/packages/locomotivemtl/charcoal-app

[dev-scrutinizer]:    https://scrutinizer-ci.com/g/locomotivemtl/charcoal-contrib-communicator/
[dev-coveralls]:      https://coveralls.io/r/locomotivemtl/charcoal-contrib-communicator
[dev-travis]:         https://app.travis-ci.com/github/locomotivemtl/charcoal-contrib-communicator

[badge-license]:      https://img.shields.io/packagist/l/locomotivemtl/charcoal-contrib-communicator.svg?style=flat-square
[badge-version]:      https://img.shields.io/packagist/v/locomotivemtl/charcoal-contrib-communicator.svg?style=flat-square
[badge-scrutinizer]:  https://img.shields.io/scrutinizer/g/locomotivemtl/charcoal-contrib-communicator.svg?style=flat-square
[badge-coveralls]:    https://img.shields.io/coveralls/locomotivemtl/charcoal-contrib-communicator.svg?style=flat-square
[badge-travis]:       https://img.shields.io/travis/com/locomotivemtl/charcoal-contrib-communicator.svg?style=flat-square

[psr-1]:  https://www.php-fig.org/psr/psr-1/
[psr-2]:  https://www.php-fig.org/psr/psr-2/
[psr-3]:  https://www.php-fig.org/psr/psr-3/
[psr-4]:  https://www.php-fig.org/psr/psr-4/
[psr-6]:  https://www.php-fig.org/psr/psr-6/
[psr-7]:  https://www.php-fig.org/psr/psr-7/
[psr-11]: https://www.php-fig.org/psr/psr-11/
