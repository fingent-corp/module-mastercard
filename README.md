<p align="center">
<a href="https://www.fingent.com/"><img alt="Fingent logo" height="50px" src="https://www.fingent.com/wp-content/uploads/Fingent-Logo-01.png"/></a>&nbsp;&nbsp;<img alt="MC logo" height="50px" src="https://www.mastercard.co.in/content/dam/public/mastercardcom/in/en/logos/mc-logo-52.svg"/>
</p>

## Overview
Payments through this module are processed securely via the Mastercard Payment Gateway. This ensures that card data is managed in compliance with all legal requirements. The gateway monitors every transaction and handles sensitive payment data on PCI Level 1 certified servers, simplifying PCI compliance for your business.

## Compatibility
Extension’s latest version supports GraphQl and is compatible with Magento version 2.4.7

### Customer Information Shared with Gateway
This module shares the following customer information with the gateway:
- Customer Billing and Shipping Address
- Customer Name
- Customer Phone Number
- Customer Email Address
- Cart Line Items (optional)

## Feature Support

Magento 2 Mastercard Payment Gateway Service module supports the following list of features:

- **Card Payments** <br/>
  Ability to process credit & debit card Payments.
- **Hosted Checkout**<br/>
  Allows you to collect payment details from your payer through an interaction hosted and displayed directly through Mastercard.
- **Google Pay (only supported in Hosted Checkout)**<br/>
  Users will have the capability to make payments utilizing the Google Pay option within the Hosted checkout. Please ensure that the MID has Google Pay enabled for this payment option to appear on the checkout page.
- **Hosted Payment Session**<br/>
  Allows to take control over the layout and styling of your payment page, while reducing PCI compliance.
- **Automated Clearing House (ACH)**<br/>
  Supports electronic bank-to-bank payment type.
- **Address Verification Service (AVS)**<br/>
  Address Verification Services is a fraud deterrent service that protects against fraudulent use of cards in non-face-to-face transactions by verifying the cardholders’ billing address. This must first be enabled on a merchant account.
- **3D Secure v1**<br/>
  Ability to authenticate cardholders using 3DS1. This must first be enabled on a merchant account.
- **3D Secure v2**<br/>
  Ability to authenticate cardholders using 3DS2. This must first be enabled on a merchant account.
- **Capture Payments**<br/>
  The manual process of capturing funds for the authorized/verified orders can be done via the Backend.
- **Full Refunds**<br/>
  Ability to refund the full transaction amount into the cardholder’s account.
- **Partial Refunds**<br/>
  Ability to refund the partial transaction amount into the cardholder’s account.
- **Void Transaction**<br/>
  Ability to perform a void transaction. This functionality is specifically available for non-invoiced orders, which are typically orders that have been authorized but not yet invoiced and are configured as “Authorize Only” orders.
- **REST & GraphQL API**<br/>
  Mastercard Magento Plugin helps to connect with Mastercard Payment Service APIs and process the payments.

Headless Magento integration decouples the website parts which would give flexibility to customize. In this scenario we consider that the front end is developed in any front end technology React / Angular and the for backend Magento built in admin module is utilized.
The Headless Magento architecture, coupled with GraphQL, enables us to deliver a more responsive and personalized user interface. This enhancement provides the ability to adapt and incorporate future payment innovations seamlessly.

## System Requirements
The latest release of the plugin has the following system requirements:
- PHP version 8.1 or higher is mandatory.
- Magento version 2.4.6 or later is required. However, we strongly recommend using the latest available version for optimal performance.

## Installation
Please refer to this [guide](https://experienceleague.adobe.com/en/docs/commerce-admin/start/resources/commerce-marketplace).

## Configuration
### Configuration steps
Please follow these steps to configure the module:
1. Login to Magento Admin dashboard.
2. Go to Stores > Configuration > Sales > Payment Methods
3. Expand OTHER PAYMENT METHODS, then Mastercard Payment Gateway Services
4. Fill in configuration details.
5. Click Save Config to store the configuration.
6. Follow Magento instructions and clean application cache, to make sure that the new payment method is immediately available in your online store.

### Configuration details
Under the Mastercard Payment Gateway Services options group, you can configure the payment options - Hosted Checkout, Hosted Payment Session, and Automated Clearing House (ACH). Each of these options can be configured individually. For detailed instructions on how to set up each option, please refer to the [Configuration](https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-payment-gateway-services/configuration) page in the Wiki.

## Documentation

The official documentation for this module is available on: [https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-payment-gateway-services/overview-and-feature-support](https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-payment-gateway-services/overview-and-feature-support)

## Support
For customer support: [https://mpgsfgs.atlassian.net/servicedesk/customer/portals](https://mpgsfgs.atlassian.net/servicedesk/customer/portals/)
