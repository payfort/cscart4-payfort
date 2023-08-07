# Amazon Payment Services add-on for CSCart
<a href="https://paymentservices.amazon.com/" target="_blank">Amazon Payment Services</a> plugin offers seamless payments for CSCart platform merchants.  If you don't have an APS account click [here](https://paymentservices.amazon.com/) to sign up for Amazon Payment Services account.


## Getting Started
We know that payment processing is critical to your business. With this plugin we aim to increase your payment processing capabilities. Do you have a business-critical questions? View our quick reference [documentation](https://paymentservices.amazon.com/docs/EN/index.html) for key insights covering payment acceptance, integration, and reporting.


## Configuration and User Guide
You can download the archive [file](/cscart-aps.zip) of the plugin and easily install it via CSCart admin screen.
CSCart add-on user guide is included in the repository [here](https://github.com/payfort/cscart4-payfort/wiki) 

# Installation
## From Admin Panel
1. Login to [Admin Panel] of CS-Cart website 
1. Navigate to Add-ons → Manage Add-ons -> CS-Cart 
1. Click on gear button to select the option Manual Installation from header Section 
1. Click on “Local” and choose the add-on zip file 
1. Navigate to Add-ons → Manage Add-ons -> Amazon Payment Services, check the status if not Active then Activate it 
1. Follow the configuration steps mentioned in Step 3 
## From Backend Server
1. Login to [Admin Panel] of CS-Cart website 
1. Navigate to Add-ons → Manage Add-ons -> CS-Cart 
1. Click on gear button to select the option Manual Installation from header Section 
1. Click on “Server” and choose the add-on zip file at remote location. 
1. Navigate to Add-ons → Manage Add-ons -> Amazon Payment Services, check the status if not Active then Activate it 
1. Follow the configuration steps mentioned in Step 3 
## From a Remote URL
1. Login to [Admin Panel] of CS-Cart website 
1. Navigate to Add-ons → Manage Add-ons -> CS-Cart 
1. Click on gear button to select the option Manual Installation from header Section 
1. Click on “Url” and enter the link for  add-on zip file at remote location. 
1. Navigate to Add-ons → Manage Add-ons -> Amazon Payment Services, check the status if not Active then Activate it 
1. Follow the configuration steps mentioned in Step 3 
## Configuration

Follow the below instruction to access configuration page of APS CS-Cart add-on:  

1. Navigate to Administration -> Payment method 
1. Under Configure section find a payment methods names as “Amazon Payment Services” 
1. Click on Title(“Amazon Payment Services”) to configure the add-on 
   

## Payment Options

* Integration Types
   * Redirection
   * Merchant Page
   * Hosted Merchant Page
   * Installments
   * Embedded Hosted Installments

* Payment methods
   * Mastercard
   * VISA
   * American Express
   * VISA Checkout
   * valU
   * mada
   * Meeza
   * KNET
   * NAPS
   * Apple Pay
   

## Changelog

| Plugin Version | Release Notes |
| :---: | :--- |
| 2.0.1 |   * Fix - PHP 8 compatibility |
| 2.0.0 |   * Integrated payment options: MasterCard, Visa, AMEX, mada, Meeza, KNET, NAPS, Visa Checkout, ApplePay, valU <br/> * Tokenization is enabled for Debit/Credit Cards and Installments <br/> * Partial/Full Refund, Single/Multiple Capture and Void events are manage in CSCart order management screen <br /> * ApplePay is activated in Product and Cart pages <br /> * Installments are embedded in Debit/Credit Card payment option | 


## API Documentation
This plugin has been implemented by using following [API library](https://paymentservices-reference.payfort.com/docs/api/build/index.html)


## Further Questions
Have any questions? Just get in [touch](https://paymentservices.amazon.com/get-in-touch)

## License
Released under the [MIT License](/LICENSE).
