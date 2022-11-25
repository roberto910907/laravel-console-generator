# Generates Laravel models and migrations using the command line

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roberto910907/laravel-console-generator.svg?style=flat-square)](https://packagist.org/packages/roberto910907/laravel-console-generator)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/roberto910907/laravel-console-generator/run-tests?label=tests)](https://github.com/roberto910907/laravel-console-generator/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/roberto910907/laravel-console-generator/Fix%20PHP%20code%20style%20issues?label=code%20style)](https://github.com/roberto910907/laravel-console-generator/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/roberto910907/laravel-console-generator.svg?style=flat-square)](https://packagist.org/packages/roberto910907/laravel-console-generator)

## Installation

You can install the package via composer:

```bash
composer require roberto910907/laravel-console-generator
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-console-generator-config"
```

This is the contents of the published config file:

```php
return [
];
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="laravel-console-generator-views"
```

## Usage

```php
$consoleGenerator = new ConsoleGenerator\ConsoleGenerator();
echo $consoleGenerator->echoPhrase('Hello, ConsoleGenerator!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Roberto Rielo](https://github.com/roberto910907)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
