# AntiScanScanClub

[![GitHub (pre-)release](https://img.shields.io/github/release/noobsec/AntiScanScanClub-laravel/all.svg)](https://github.com/noobsec/AntiScanScanClub-laravel/releases)
[![Build Status](https://img.shields.io/travis/noobsec/AntiScanScanClub-laravel/master.svg)](https://travis-ci.org/noobsec/AntiScanScanClub-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/noobsec/antiscanscanclub-laravel.svg)](https://packagist.org/packages/noobsec/antiscanscanclub-laravel)
![GitHub](https://img.shields.io/github/license/mashape/apistatus.svg)
[![GitHub issues](https://img.shields.io/github/issues/noobsec/AntiScanScanClub-laravel.svg)](https://github.com/noobsec/AntiScanScanClub-laravel/issues)
![GitHub closed pull requests](https://img.shields.io/github/issues-pr-closed/noobsec/AntiScanScanClub-laravel.svg)
[![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?style=flat)](https://github.com/noobsec/AntiScanScanClub-laravel/issues)

A Laravel Package to Block Automated Scanners from Scanning your Site.

---

-   [Installation](#installation)
-   [Configuration](#configuration)
-   [Usage](#usage)
-   [Changelog](#changelog)
-   [Contributing](#contributing)
-   [Security](#security)
-   [Credits](#credits)
-   [License](#license)
-   [Version](#version)

---

## Installation

```bash
$ composer require noobsec/antiscanscanclub-laravel
```

## Laravel 5+

### Setup

1. Publish the config file

```ssh
php artisan vendor:publish --provider="noobsec\AntiScanScanClub\AntiScanScanClubServiceProvider"
```

2. Create middleware

```bash
$ php artisan make:middleware AntiScanScanMiddleware
```

## Configuration

1. Add `ASSC_LIST` in **.env** file:

_**NOTE: Blacklists file will be stored in `storage/app/` path**_

```
ASSC_LIST="blacklists.json"
```

2. Edit the _AntiScanScanMiddleware_ file _(app/Http/Middleware/AntiScanScanMiddleware.php)_, approx like this:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use noobsec\AntiScanScanClub\AntiScanScanClub;

class AntiScanScanMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $ASSC = new AntiScanScanClub();
        $ASSC->checkIp($request->ip());
        $ASSC->filterInput($request, TRUE);
        return $next($request);
    }
}
```

3. Add middleware to global HTTP middleware stack, edit _Kernel_ file _(app/Http/Kernel.php)_:

```php
    protected $middleware = [
    	...
        \App\Http\Middleware\AntiScanScanMiddleware::class,
    ];
```

## Usage

-   **Init AntiScanScanClub source**

```php
use noobsec\AntiScanScanClub\AntiScanScanClub;

$ASSC = new AntiScanScanClub();
```

-   **Check whether the client IP has been blocked or not**

```php
$clientIp = '127.0.0.1';

var_dump($ASSC->checkIp($clientIp)); // @return void/bool
```

-   **Add client IP to blacklists files**

```php
$clientIp = '127.0.0.1';
$attack_type = 'Added manually';

var_dump($ASSC->addToBlacklisted($clientIp, $attack)); // @return bool
```

-   **Prevention of illegal input based on filter rules**

**_NOTE: If you call `filterInput()`, you no longer need to call `addToBlacklisted()` method._**

```php
$data = [
	"input" => "Test payload",
	"textarea" => "<object/onerror=write`1`//"
];
$blocker = TRUE;
$clientIp = '127.0.0.1';

$ASSC->filterInput($data, $blocker, $clientIp); // @return void/bool
```

-   **Remove client IP from blacklists file**

```php
$clientIp = '127.0.0.1';

var_dump($ASSC->removeFromBlacklists($clientIp)); // @return bool
```

-   **Purge and/ clean all client IPs from blacklists file**

```php
var_dump($ASSC->purgeBlacklistsFile()); // @return bool
```

## Changelog

Please see the [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email root@noobsec.org instead of using the issue tracker.

## Credits

-   [noobSecurity](https://github.com/noobsec)
-   [expose](https://github.com/enygma/expose)
-   [All Contributors](../../contributors)

## License

license. Please see the [LICENSE file](LICENSE.md) for more information.

## Version

**Current version is 1.0.1** and still development.
