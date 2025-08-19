# Changelog
All notable changes to this project will be documented in this file.

## [2.4.7] - 2025-08-19
### Enhancement
- Added Backward compatibility with Magento’s previous platform versions 2.4.3, 2.4.4, 2.4.5 and 2.4.6.
- Introduced a new ‘Merchant Information’ section in the plugin settings, allowing admins to update the merchant details to be displayed on the Redirected Payment Page easily.
- Minor changes in the plugin configuration.
- Applied Terms and Conditions validation at checkout in line with Magento configuration.

## [2.4.6] - 2025-04-09
### Enhancement
- Introduced a 'Place Order' button to redirect customers to the Gateway payment page.

## [2.4.5] - 2025-03-10
### Enhancement
- Added PayPal (Supported in Hosted Checkout Only).
- The plugin will capture the download count from GitHub and track active installations, including Store Name, Store URL, and Country, once the Test Mode is set as No and the API Username and API Password are saved.
## Fixed
- Resolved an issue where line items were not correctly displayed in the Redirected Payment Page.
- Minor bug fixes.

## [2.4.4] - 2024-12-24
### Enhancement
- Mastercard API upgraded to version 100.
- Added a new "Funding Status" parameter for orders placed through Hosted Checkout and Hosted Session.
### Changed
- Reintroduced VATO payment option for ACH.
### Fixed
- Minor bug fixes.

## [2.4.3] - 2024-11-04
### Changed
- Refund added for ACH.
- ACH orders will be processed based on webhooks.

## [2.4.2] - 2024-10-24
### Enhancement
- Mastercard API upgraded to version 84.
- Introduced Checkout Form Type – Redirect to Payment Page in Hosted Checkout Integration Model.
- Void Transaction – status updated to “Canceled”.
### Fixed
- Fixed the Pay button amount refresh issue in Hosted Checkout.

## [2.4.0] - 2024-09-02
### Enhancement
- Module is now compatible with Magento version 2.4.7-p2.

## [2.3.6] - 2024-08-06
### Enhancement
- Mastercard API version upgraded to 81.
- Module is now compatible with Magento version 2.4.6 p6.
- Implemented a notification feature to alert the admin whenever a new version is launched on GitHub/Marketplace.
### Changed
- Hosted Session – The VATO option will only be supported when 3DS is disabled.
- ACH will not support the VATO payment option as it does not allow capturing the amount.

## [2.3.5] - 2024-02-02
### Enhancement
- Mastercard API version upgraded to 73.
- Enhanced GraphQL Support
### Changed
- Hosted checkout form visibility changed to embedded form rather than lightbox.
### Fixed
- Minor bug fixes.

## [2.3.2] - 2024-01-16
### Enhancement
- Added support for GraphQL for payment capturing.

## [2.3.1] - 2023-07-04
### Enhancement
- Added compatibility with PHP 8.
- Module is now compatible with Magento version 2.4.6 and higher.

## [2.2.0] - 2023-06-23
### Feature
- Compatible with Magento from v2.3.5 to v2.4.5
- Initial module version with MasterCard API integrated
- Card payments
- Hosted Session
- Hosted Checkout
- Full refunds
- Partial refunds
- AVS
- 3DS1
- Tokenisation
