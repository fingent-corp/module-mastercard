<p align="center">
<a href="https://www.fingent.com/"><img alt="Fingent logo" height="50px" src="https://www.fingent.com/wp-content/uploads/Fingent-Logo-01.png"/></a>&nbsp;&nbsp;<img alt="MC logo" height="50px" src="https://mpgs.fingent.wiki/wp-content/uploads/2025/04/mastercard-logo.png"/>
</p>

## Overview
<a href="https://www.mastercard.co.in/en-in.html">Mastercard Inc.</a> is the <a href="https://www.investopedia.com/terms/m/mastercard-card.asp">world’s second-largest payment processing corporation </a>, providing a variety of payment solutions and services. We connect people, businesses, and organizations across over 210 countries and territories, creating opportunities for more people in more places, now and for the future. This module lets you add multiple payment options to your checkout, enabling secure credit, debit and account payments on your Magento-powered website.

Payments made through this module are processed using the trusted Mastercard Gateway. It securely handles card/account details, following strict legal and regulatory requirements, ensuring a safe experience for both businesses and customers.

We carefully monitor every transaction to catch and stop fraud, making sure your payments are safe and secure. Your sensitive payment details, like card/account information, are handled and stored on servers with the highest level of security certification, that is PCI (Payment Card Industry) Level 1.

With this gateway, you don’t have to handle or store customer card/account details yourself. This makes meeting PCI compliance much easier for your business. You can focus on running your store while the gateway securely processes payments for you.

## Compatibility
The Mastercard Gateway Magento extension works with these Magento platforms:
- Community/Open-Source Edition
- Enterprise/Commerce/Cloud Edition

Plugin versions 3.0.0 and 3.0.1 are developed and tested for compatibility with Magento version 2.4.7 and 2.4.8.

Note : Plugin version 2.4.7 has been tested for compatibility only with Magento versions 2.4.3 to 2.4.7.

## Mastercard Payment Module Features

The Mastercard Payment Module is packed with tools to make payment processing easier and safer for your business. Here's a quick look at its main features:

 <b>1. Payment Methods </b> - Defines the types of payment options supported, which are:
 
- **Card Payments** <br/>
   Easily and securely accept both credit and debit card payments. This feature works with major card brands, making it simple and reliable for your customers to pay.
- **Google Pay (Supported in Hosted Checkout Only)**<br/>
   With Google Pay, customers can quickly and easily pay on the hosted checkout page. To enable this option, ensure your Merchant Identification (MID) is configured for Google Pay. This makes payments smooth and hassle-free, allowing customers to complete transactions with just a few taps.
- **PayPal (Supported in Hosted Checkout Only)**<br/>
   With PayPal, customers can make payments quickly and effortlessly through the hosted checkout page. To use this option, ensure that your Merchant Identification (MID) is set up for PayPal transactions. Once enabled, this feature provides a seamless and hassle-free payment experience, allowing customers to complete their purchases with just a few simple taps.
- **KNET (Supported in Hosted Checkout Only)**<br/>
   With KNET, customers can make payments quickly and effortlessly through the hosted checkout page. To use this option, ensure that your MID is set up for KNET transactions. Once enabled, this feature provides a seamless and hassle-free payment experience, allowing customers to complete their purchases with just a few simple taps.
- **Automated Clearing House (ACH)**<br/>
   Automated Clearing House (ACH) payments let customers pay directly from their bank accounts through electronic transfers, making bank-to-bank payments easy and fast.

<b>2. Checkout and Payment Integration </b>- These features focus on methods of collecting payment details from customers:

- **Hosted Checkout**<br/>
   This feature lets your customers enter their payment details on a readymade secure checkout page provided directly by Mastercard. It keeps sensitive information safe while giving your customers a smooth and hassle-free payment experience.
- **Hosted Payment Session**<br/>
   This feature lets you customize the layout and design of your payment page to match your brand, while still meeting strict PCI security standards. It makes managing security easier without compromising the user experience.
  
<b> 3. Fraud Prevention and Security </b>- These features enhance security and protect against fraud:
 
- **Address Verification Service (AVS)**<br/>
   AVS helps prevent fraud by checking the billing address provided during a payment to make sure it matches the one on file with the cardholder's bank. This helps confirm that the person making the payment is the actual cardholder. To use AVS, it must be activated on your MID.
- **EMV 3-D Secure v1**<br/>
   EMV 3D Secure (3DS1) adds an extra step to verify the cardholder during online transactions. This helps prevent unauthorized payments by asking the cardholder to confirm their identity. Before using this feature, make sure it's enabled on your MID.
- **EMV 3-D Secure v2**<br/>
   EMV 3DS2 in the Mastercard Gateway, is the latest version of the security protocol, designed to enhance security in online purchases while providing frictionless checkouts to payers who are considered low risk by the Access Control Server (ACS). The ACS determines the risk using information provided by the merchant, browser fingerprinting, and previous interactions with the payer. Please note that this needs to be activated on your Merchant Identification (MID) before you can use it.
- **Tokenization**<br/>
   Tokenization improves security by replacing sensitive card or account details (like your 16-digit Card number or Bank Account Number or Routing Number) with a unique, encrypted token which is created by Mastercard Gateway and sent to the merchant. This token can be used for future transactions, keeping your card information safe and private. To use Tokenization, it must be activated on your MID.
  
<b>4. Transaction Management </b>- These features support the processing and management of transactions:

- **Capture Payments**<br/>
   This feature lets you manually process payments for authorized orders directly from your system. It gives you more control over how payments are handled.
- **Void Transaction**<br/>
   The void transaction feature lets you cancel an order before it's invoiced or completed. This option is usually available for 'Authorize Only' transactions, where the funds are reserved but not yet charged or billed.
- **Full Refunds**<br/>
   You can refund the entire amount of the transaction back to the customer's account. This is helpful when a complete order needs to be canceled or returned.
- **Partial Refunds**<br/>
   This feature lets you refund only part of an order, giving the customer the specific amount they are entitled to.
  
<b> 5. Headless Capabilities </b>- This feature provides headless functionality:
 
 - **REST & GraphQL API**<br/>
   The Mastercard Magento Plugin connects with Mastercard's Payment Service APIs to help you process payments smoothly. It also supports Headless Magento integration, which separates the front-end and back-end systems. This allows you to easily customize the user experience, whether you're using React, Angular, or another front-end technology.

   The Headless Magento architecture, paired with GraphQL, creates a flexible and responsive user interface. This makes it easier to add new features and payment options as they come up, ensuring that your payment solution stays up-to-date and can grow with your needs.

### Disclaimer!
Starting from version 2.4.5, the plugin will collect analytics data, including the plugin download count from GitHub and the active installation count. The Active count, Store Name, Store URL, and the Country configured in the Magento 2 Admin page will be captured once the Test Mode is set as No and the API Username and API Password are saved in the configuration page.

## Documentation

The official documentation for this module is available on [Wiki site](https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-gateway/overview-and-feature-support).

## Installation of Module
For more information, please refer to the [Wiki documentation](https://mpgs.fingent.wiki/enterprise/magento-2-mastercard-gateway/installation).


## Support
For additional support, please visit the [Support Portal](https://mpgsfgs.atlassian.net/servicedesk/customer/portals).

