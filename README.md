# AntiScanScanClub

[![GitHub (pre-)release](https://img.shields.io/github/release/noobsec/AntiScanScanClub-laravel/all.svg)](https://github.com/noobsec/AntiScanScanClub-laravel/releases)
[![Build Status](https://img.shields.io/travis/noobsec/AntiScanScanClub-laravel/master.svg)](https://travis-ci.org/noobsec/AntiScanScanClub-laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/noobsec/antiscanscanclub-laravel.svg)](https://packagist.org/packages/noobsec/antiscanscanclub-laravel)
[![LICENSE](https://img.shields.io/github/license/mashape/apistatus.svg)](LICENSE)
[![GitHub issues](https://img.shields.io/github/issues/noobsec/AntiScanScanClub-laravel.svg)](https://github.com/noobsec/AntiScanScanClub-laravel/issues)
[![GitHub closed pull requests](https://img.shields.io/github/issues-pr-closed/noobsec/AntiScanScanClub-laravel.svg)](../../pulls?q=is%3Apr+is%3Aclosed)
[![Contributions Welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg)](https://github.com/noobsec/AntiScanScanClub-laravel/issues)

A Laravel Package to Block Automated Scanners from Scanning your Site.

![how_antiscanscanclub_work](https://user-images.githubusercontent.com/25837540/47261277-ae3b5480-d4f5-11e8-8055-aaf090f198c4.png)

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

-   Please keep up-to-date this package to latest commit

```bash
$ composer require noobsec/antiscanscanclub-laravel:dev-master
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
        $blocker = TRUE;
        $ASSC->checkIp($request->ip());

        if ($request->isMethod('GET') && $request->getQueryString() === NULL) {
            /**
             * Prevention of access to credentials and/ important files/path
             * (e.g: wp-admin.php, .git/, backups.tar.gz, www.sql)
             */

            $ASSC->filterFile($request->getPathInfo(), $blocker, $request->ip());
        } else {
            $ASSC->filterInput($request->all(), $blocker, $request->ip());
        }

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

```php
$data = [
    "input" => "Test payload",
    "textarea" => "<object/onerror=write`1`//"
];
$blocker = TRUE;
$clientIp = '127.0.0.1';

$ASSC->filterInput($data, $blocker, $clientIp); // @return void/bool
```

-   **Prevention of access to credentials and/ important files/path**

**e.g: `wp-admin.php`, `.git/`, `backups.tar.gz`, `www.sql`** _(see many more at [filter_files.txt](src/filter_files.txt))_

```php
$url = "/wp-admin.php";
$blocker = TRUE;
$clientIp = '127.0.0.1';

$ASSC->filterFile($url, $blocker, $clientIp); // @return void/bool
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

-   **Whitelisting one files/path from filterFile() rejection**

```php
var_dump($ASSC->whitelistFile('wp-admin.php')); // @return bool
```

-   **Whitelisting all public files recursively from filterFile() rejection**

```php
var_dump(whitelistPublicFiles()); // @return array
```

-   **Whitelisting uri of all registered routes from filterFile() rejection**

```php
var_dump(whitelistAllRoutes()); // @return array
```

-   **Add file and/ path to filterFile() rejection**

```php
$file = "api/adminLists";

var_dump(addToFilterFiles($file)); // @return integer/bool
```

-   **Restoring filterFile() rules to default**

```php
var_dump($ASSC->restoreFilterFiles()); // @return bool
```

### NOTE

-   If you call `filterInput()` and/ `filterFile()` method, you no longer need to call `addToBlacklisted()` method.
-   Or if you want to call `whitelistFile()`, `whitelistPublicFiles()` and/ `whitelistAllRoutes()` method, make sure this is called before `filterFile()` and/ `searchIp()` method _(or comment these methods, please check middleware)_.

## Changelog

Please see the [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email root@noobsec.org instead of using the issue tracker.

## Credits

-   [noobSecurity](https://github.com/noobsec)
-   [dwisiswant0](https://github.com/dwisiswant0)
-   [enygma](https://github.com/enygma)
-   [maurosoria](https://github.com/maurosoria)
-   [All Contributors](../../contributors)

## License

license. Please see the [LICENSE file](LICENSE) for more information.

## Version

**Current version is 2.0.2** and still development.
