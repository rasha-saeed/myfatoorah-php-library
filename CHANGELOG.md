# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

------------------------------
## [2.2.11] - 2026-11-09
### Changed
- Relicense from GPL-3.0-only to the MIT License. The library exists so that any PHP developer can call the MyFatoorah API, take the code, change it and ship it inside their own product; MIT says exactly that, and its one condition - keeping the copyright notice - is what marks this as the official MyFatoorah library. The notice lives in `autoload.php`, which the self-update never rewrites and which keeps its comments, unlike the copy downloaded from the portal.
- Stop the self-update in a working copy with a `.mf-env` file holding the word `development` instead of an unreachable `return;`. Anyone who wants the auto-update off can create that file; with no such file - the normal case - the update runs as before, at the cost of one `stat()`.
- Shorten the file header of `autoload.php` and drop the class docblocks from `src/`: they restated the class name, and two of them named the wrong class.
- Update the docblocks from `double` to `float` to match the casts.
- Upgrade the development toolchain (PHPUnit 13, PHPStan 2, PHP_CodeSniffer 4, PHPCompatibility 10, phan 6) and move the test suites from `__construct()` to `setUp()`.
- Run the quality checks through composer scripts (`composer check`), the single entry point.
- Complete the docblocks across `autoload.php` and `src/`: every function now carries a description, its parameter descriptions and a `@return` tag.
- Correct the wording of the `Worng event.` exception thrown by `MyFatoorahWebhook`; it now reads `Wrong event.`.
- Rename the `$KeyType` parameter of `getPaymentStatus()` to `$keyType`, matching `refund()` and the rest of the library. It only affects callers that pass it as a named argument.
- Supported PHP versions are unchanged: the library still installs on PHP 7.0 and up.

- Report a failed configuration update to the server error log. Every message starts with `MyFatoorah:` and names the cause: no write permission on a named path, the HTTP code the portal answered with, or the cURL error behind a failed connection. The report goes through `error_log()` and not `trigger_error()`, which a framework can turn into an exception on a merchant whose error handling is strict.
- Stop with an exception that names the cause when there is no cached configuration at all. The library already stopped in that case, with `The "vcCode" key must be one of ().`, which told the merchant nothing; it now says what went wrong, which file is missing, and that the shipped `mf-config.json` can be restored or MyFatoorah disabled until the portal answers.
- Cover the countries configuration cache with a test suite of its own: the shipped file is readable, a fresh file is read without calling the portal, and each way the update can fail is reported or stopped as intended. It does not reach the Composer package.

### Fixed
- Ask the portal only when the answer can be saved. Where the cache file or its folder was read-only, the download ran on every page view and was thrown away: the site worked from the cache so nothing looked wrong, while a thousand page views an hour meant a thousand downloads. The write permission is now checked first, and no request is made that cannot be acted on.
- Keep the cached configuration when the portal answers with something that is not the configuration. An HTTP 200 carrying an HTML error page used to be written straight into `mf-config.json`, so the merchant lost the configuration until a later download succeeded. The answer is now parsed before it replaces anything.
- Always return an array from `getMFCountries()`. Invalid JSON in the cache made it return `null`, and the `array_keys()` call in the caller raised a `TypeError` that no `catch (Exception)` could hold, so a damaged cache file took the page down instead of falling back to an empty list.
- Read the countries configuration once while building a MyFatoorah object instead of twice. The constructor looked it up and `setVcCode()` looked it up again, so an unreachable portal cost two timeouts on the same page and wrote the same line in the log twice.
- Give the mf-config.json request a connect timeout of 3 seconds and a transfer timeout of 6. It had none, and it runs on the critical path of a merchant's page: once an hour the first request that builds a MyFatoorah object pays the download, so a slow portal was a slow checkout page.
- Reset the cache file's timestamp before fetching it. It used to be reset only on an HTTP 403, so any other failure - a timeout, a DNS error, a 500 - left the timestamp old and every following request tried the download again; with no timeout on that request, a portal that was merely slow became a site that was slow on every page load. The 403 branch is gone with it: it read the cached file and returned it, which is what `getMFCountries()` already does when the fetch comes back empty, and on a missing file it left an empty one behind that blocked the next attempt for an hour.
- Make the library safe on PHP 8.5: every `curl_close()` call (deprecated since 8.5, without effect since 8.0) now releases the handle instead, and the non-canonical `(double)` cast is replaced by `(float)`.
- Replace the `curl_close()` call left in the loader that ships with the manual install. It is deprecated since PHP 8.5, so a merchant who installed the library by hand saw a warning that a Composer install did not.
- Build the digit list in `convertArabicDigitstoEnglish()` from an explicit string array instead of `range(0, 9)`, whose return type is not stable across PHP versions.

### Security
- Pass explicit flags to `htmlspecialchars()` in `MyFatoorahHelper::filterInputField()`. The default flags changed in PHP 8.1, so on PHP 8.0 and older single quotes were left unescaped and a filtered value could break out of a single-quoted HTML attribute.

------------------------------
## [2.1.5] - 2026-11-09
### Added
- Add `executePayment()`, correctly spelled.

### Changed
- Relicense from GPL-3.0-only to the MIT License. The library exists so that any PHP developer can call the MyFatoorah API, take the code, change it and ship it inside their own product; MIT says exactly that, and its one condition - keeping the copyright notice - is what marks this as the official MyFatoorah library. The notice lives in `autoload.php`, which the self-update never rewrites and which keeps its comments, unlike the copy downloaded from the portal.
- Stop the self-update in a working copy with a `.mf-env` file holding the word `development` instead of an unreachable `return;`. Anyone who wants the auto-update off can create that file; with no such file - the normal case - the update runs as before, at the cost of one `stat()`.
- Bring `autoload.php` in line with the 2.2 branch: a connect timeout of 3 seconds and a transfer timeout of 6, where this branch had none and a slow portal could hold up a page; an explicit return after a cURL error; a warning when the update server answers with anything other than a readable 200, which used to pass in silence; and every message tagged `MyFatoorah Library:`.
- Shorten the file header of `autoload.php` and drop the class docblocks from `src/`: they restated the class name, and two of them named the wrong class.
- Update the docblocks from `double` to `float`.
- Upgrade the development toolchain (PHPUnit 13, PHPStan 2, PHP_CodeSniffer 4, PHPCompatibility 10) and move the test suites from `__construct()` to `setUp()`.
- Run the quality checks through composer scripts (`composer check`).
- Reformat the source to PSR-12.
- Complete the docblocks across `autoload.php` and `src/`: every function now carries a description, its parameter descriptions and a `@return` tag.
- Rename the misspelled `$currncy` parameter of `getPaymentStatus()` to `$currency`, and its `$KeyType` parameter to `$keyType`, matching the rest of the library. Both only affect callers that pass them as named arguments.
- Correct the wording of the `Phone Number lenght` exception thrown by `getPhone()`; it now reads `length`, matching the 2.2 branch.
- Supported PHP versions are unchanged: the library still installs on PHP 7.0 and up.

- Bring the countries configuration cache in line with the 2.2 branch: the write permission is checked before the portal is asked, so a read-only file or folder no longer causes a download on every page view; the answer is parsed before it replaces the cache, so an HTML error page cannot overwrite it; the byte order mark is stripped as 2.2 does; and a failed update is reported through `error_log()` instead of `trigger_error()`, in the same words as 2.2. Unlike 2.2 this branch does not stop when nothing is cached - it keeps falling back to the default API URL, as it always has.

### Deprecated
- `excutePayment()` is kept as a wrapper that forwards to `executePayment()`, so existing callers are unaffected. A subclass that **overrides** `excutePayment()` is no longer reached and should override `executePayment()` instead.

### Fixed
- Create `mf-config.json` when it is missing and the folder allows it. The branch used to return an empty list for good, which sends a merchant outside Kuwait to the Kuwait API URL without a word in any log; an installation that lost the file stayed on the wrong URL until someone put it back by hand.
- Give the mf-config.json request a connect timeout of 3 seconds and a transfer timeout of 6. It had none, and it runs on the critical path of a merchant's page: once an hour the first request that builds a MyFatoorah object pays the download, so a slow portal was a slow checkout page.
- Make the library safe on PHP 8.5: every `curl_close()` call (deprecated since 8.5, without effect since 8.0) now releases the handle instead, and the non-canonical `(double)` cast is replaced by `(float)`.
- Replace the `curl_close()` call left in the loader that ships with the manual install. It is deprecated since PHP 8.5, so a merchant who installed the library by hand saw a warning that a Composer install did not.
- Build the digit list in `getPhone()` from an explicit string array instead of `range(0, 9)`, whose return type is not stable across PHP versions.
- Correct the `Excute Payment` label written to the log file; it now reads `Execute Payment`, matching the 2.2 branch.

------------------------------
## [2.2.10] - 2026-07-12
### Added
- Introduced support for MyFatoorah V3 payments/sessions API

### Changed
- Update autoload.php's self-update routine to:
  1. generate the new V3 class files
  2. add curl connect/read timeouts so a slow update server can't hang the request indefinitely
  3. surface previously silent failures (bad HTTP status/response shape) via trigger_error()

### Fixed
- Replace arrow functions with anonymous functions for compatibility with older PHP versions

------------------------------
## [2.2.9] - 2026-02-12
### Added
- Introduced support for Webhook v2
- Add STC to be displayed in the card section (redirection)

### Changed
- Optimize the "add Gateway To Checkout" function
- Limited embedded Apple Pay / Google Pay to a single gateway
- Apply the regex expression for the customer name, invoice item names, item product names, and item product description

### Fixed
- Fix displaying of Arabic letters in the log

------------------------------
## [2.1.4] - 2024-12-02
### Changed
- Remove the mf-config.json file to ensure a fresh download during installation.

------------------------------
## [2.2.8] - 2024-09-06
### Fixed
- Fix the display of the checkout price.

------------------------------
## [2.2.7] - 2024-03-31
### Fixed
- Fix the display of the checkout price.
- Trim the config JSON file to remove any hidden chars.

------------------------------
## [2.2.6] - 2024-02-11
### Added
- Add the getPaymentStatusLink static function
- Add the getOneCurrencyRate static function
- Add the getTranslatedCurrency static function to translate the currency

### Changed
- Change the config array to accept the vcCode and countryCode

------------------------------
## [2.2.5] - 2023-12-05
### Changed
- Some changes to the library to be compatible with other API rather than plugins

------------------------------
## [2.2.4] - 2023-06-26
### Added
- Add google pay gateway

------------------------------
## [2.2.3] - 2023-05-24
### Fixed
- Fix display price value in calc Gateway Data

------------------------------
## [2.2.2] - 2023-05-02
### Added
- Add makeRefund API function
- Add more test files

### Fixed
- Fix autoload file

------------------------------
## [2.1.3] - 2023-05-24
### Changed
- Modify refund

### Fixed
- Fix display price value in calc Gateway Data

------------------------------
## [2.1.2] - 2023-05-02
### Added
- Add notification options as param in send payment function

### Changed
- Modify refund

### Fixed
- Fix apple pay striptags

------------------------------
## [2.2.1] - 2023-03-30
### Added
- Add notification options as param in send payment function

### Changed
- Modify refund

### Fixed
- Fix apple pay striptags

------------------------------
## [2.2.0] - 2023-01-04
### Changed
- Restructure the library files

------------------------------
## [2.1.1] - 2023-01-04
### Fixed
- Fix many requests in the MyFatoorah autoloader

------------------------------
## [2.1.0] - 2022-09-18
### Added
- Added Apple Pay Embedded Button

### Changed
- Optimization in some functions

### Fixed
- Fix compatibility with PHP 8.1

------------------------------
## [2.0.0] - 2022-07-04
### Added
- The first version of the library

------------------------------
[2.2.11]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.11
[2.2.10]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.10
[2.2.9]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.9
[2.2.8]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.8
[2.2.7]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.7
[2.2.6]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.6
[2.2.5]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.5
[2.2.4]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.4
[2.2.3]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.3
[2.2.2]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.2
[2.2.1]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.1
[2.2.0]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.2.0
[2.1.5]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.5
[2.1.4]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.4
[2.1.3]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.3
[2.1.2]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.2
[2.1.1]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.1
[2.1.0]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.1.0
[2.0.0]: https://dev.azure.com/myfatoorahsc/Public-Repo/_git/Library?version=GT2.0.0
