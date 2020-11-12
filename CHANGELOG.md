# Enupal PayPal Changelog

## 1.3.0 - 2020.11.12
### Added
- Added support to PHP 7.4

## 1.2.0 - 2020.02.26
### Added
- Added support to Craft 3.4

### Fixed
- Fixed issue on amount with decimals ([#5])

[#5]: https://github.com/enupal/paypal/issues/5

## 1.1.0 - 2019.10.23
### Added
- Added custom URL and HTML button options to button appearance setting

## 1.0.11 - 2019.09.03
### Fixed
- Fixed issue that prevent create the order when the IPN is received

## 1.0.10 - 2019.09.02
### Fixed
- Fixed issue when generating the IPN url

## 1.0.9 - 2019.08.29
### Added
- Added support for project config and environmental variables

## 1.0.8 - 2019.07.11
### Added
- Added support for Craft 3.2

## 1.0.7 - 2018.11.19
### Fixed
- Fixed error where `HttpException` class does not exists

### Updated
-  Updated `assetBundles` folder to `web`

## 1.0.6 - 2018.07.26
### Fixed
- Fixed deprecation errors

## 1.0.5 - 2018.07.11
### Added
- Added `getButton(sku)` to PayPal Variable

## 1.0.4 - 2018.06.27
### Added
- Added better Return Url functionality
- Added `getOrders()` to PayPal Variable

### Fixed
- Fixed bug on `getOrderByNumber` service function
 
## 1.0.3 - 2018.06.22
### Added
- Added id to the PayPal Form

## 1.0.2 - 2018.06.19
### Added
- Added `getOrderByNumber`, `getOrderById` and `getAllOrders` to the `paypalButton` variable

### Fixed
- Fixed bug where notify URL for IPN was not passed by the `getAlias` function

## 1.0.1 - 2018.04.15
### Added
- Added one line of code example

## 1.0.0 - 2018.04.15
### Added
- Initial release