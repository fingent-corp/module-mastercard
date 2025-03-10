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

The Mastercard Payment Module is packed with tools to make payment processing easier and safer for your business. Here's a quick look at its main features:

 1. Payment Methods - Defines the types of payment options supported, which are:
 
- **Card Payments** <br/>
   Easily and securely accept both credit and debit card payments. This feature works with all major card brands, making it simple and reliable for your customers to pay.
- **Google Pay (only supported in Hosted Checkout)**<br/>
   With Google Pay, customers can quickly and easily pay on the hosted checkout page. To enable this option, ensure your Merchant Identification (MID) is configured for Google Pay. This makes payments smooth and hassle-free, allowing customers to complete transactions with just a few taps.
- **PayPal (Supported in Hosted Checkout Only)**<br/>
   With PayPal, customers can make payments quickly and effortlessly through the hosted checkout page. To use this option, ensure that your Merchant Identification (MID) is set up for PayPal transactions. Once enabled, this feature provides a seamless and hassle-free payment experience, allowing customers to complete their purchases with just a few simple taps.
- **Automated Clearing House (ACH)**<br/>
   ACH payments let customers pay directly from their bank accounts through electronic transfers, making bank-to-bank payments easy and fast.

2. Checkout and Payment Integration - These features focus on methods of collecting payment details from customers:

- **Hosted Checkout**<br/>
   This feature lets your customers enter their payment details on a readymade secure checkout page provided directly by Mastercard. It keeps sensitive information safe while giving your customers a smooth and hassle-free payment experience.
- **Hosted Payment Session**<br/>
   This feature lets you customize the layout and design of your payment page to match your brand, while still meeting strict PCI security standards. It makes managing security easier without compromising the user experience.
  
 3. Fraud Prevention and Security - These features enhance security and protect against fraud:
 
- **Address Verification Service (AVS)**<br/>
   AVS helps prevent fraud by checking the billing address provided during a payment to make sure it matches the one on file with the cardholder's bank. This helps confirm that the person making the payment is the actual cardholder. To use AVS, it must be activated on your Merchant Identification (MID).
- **3D Secure v1**<br/>
   3D Secure (3DS1) adds an extra step to verify the cardholder during online transactions. This helps prevent unauthorized payments by asking the cardholder to confirm their identity. Before using this feature, make sure it's enabled on your Merchant Identification (MID).
- **3D Secure v2**<br/>
   3DS2 in the Mastercard Gateway, is the latest version of the security protocol, designed to enhance security in online purchases while providing frictionless checkouts to payers who are considered low risk by the Access Control Server (ACS). The ACS determines the risk using information provided by the merchant, browser fingerprinting, and previous interactions with the payer. Please note that this needs to be activated on your Merchant Identification (MID) before you can use it.
- **Tokenization**<br/>
   Tokenization improves security by replacing sensitive card or account details (like your 16-digit Card number or Bank Account Number or Routing Number) with a unique, encrypted token which is created by MPGS and sent to the merchant. This token can be used for future transactions, keeping your card information safe and private.
  
4. Transaction Management - These features support the processing and management of transactions:

- **Capture Payments**<br/>
   This feature lets you manually process payments for authorized orders directly from your system. It gives you more control over how payments are handled.
- **Void Transaction**<br/>
   The void transaction feature lets you cancel an order before it's invoiced or completed. This option is usually available for 'Authorize Only' transactions, where the funds are reserved but not yet charged or billed.
- **Full Refunds**<br/>
   You can refund the entire amount of the transaction back to the customer's account. This is helpful when a complete order needs to be canceled or returned.
- **Partial Refunds**<br/>
   This feature lets you refund only part of an order, giving the customer the specific amount they are entitled to.
  
 5. Headless Capabilities - This feature provides headless functionality:
 
 - **REST & GraphQL API**<br/>
    The Mastercard Magento Plugin connects with Mastercard's Payment Service APIs to help you process payments smoothly. It also supports Headless Magento integration, which separates the front-end and back-end systems. This allows you to easily customize the user experience, whether you're using React, Angular, or another front-end technology.

    The Headless Magento architecture, paired with GraphQL, creates a flexible and responsive user interface. This makes it easier to add new features and payment options as they come up, ensuring that your payment solution stays up-to-date and can grow with your needs

## System Requirements
The latest release of the plugin has the following system requirements:
- PHP version 8.1 or higher is mandatory.
- Magento version 2.4.7 or later is required. However, we strongly recommend using the latest available version for optimal performance.

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

### Disclaimer!
Starting from version 2.4.5, the plugin will collect analytics data, including the plugin download count from GitHub and the active installation count. The Active count, Store Name, Store URL, and the Country configured in the Magento 2 Admin page will be captured once the Test Mode is set as No and the API Username and API Password are saved in the configuration page.

## Documentation

The official documentation for this module is available on: [https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-payment-gateway-services/overview-and-feature-support](https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-payment-gateway-services/overview-and-feature-support)

## Support
For customer support: [https://mpgsfgs.atlassian.net/servicedesk/customer/portals](https://mpgsfgs.atlassian.net/servicedesk/customer/portals/)
