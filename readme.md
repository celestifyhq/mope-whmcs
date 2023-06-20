# Mopé Gateway for WHMCS
Mopé Gateway for WHMCS allows you to accept Mopé mobile app payments in WHMCS. In order to use this payment gateway you need to contact Hakrinbank to sign the Mopé online agreement. You can find more information about Mopé at: https://mope.sr/

## WHMCS Versions Supported
Currently this payment gateway supports the following versions of WHMCS:

* 8.x

## Features
* Accept payments in SRD, EUR and USD from Mopé mobile wallet users.

## Installation
To install the Mopé module in WHMCS, download and unzip the module files, followed by uploading the files to your your WHMCS installation as follows:

1. From the module root folder, copy the file named `mope.php` and place it in the `/modules/gateways/` folder of your WHMCS installation.
2. From the `callback/` folder in the module folder, copy the other `mope.php` file and place it in the `/modules/gateways/callback` folder of your WHMCS installation.
3. Copy the `mope/` folder from the module folder into the `/modules/gateways/` folder of your WHMCS installation.

## Setup
To activate the Mopé module in WHMCS, navigate to **Configuration () > System Settings > Payment Gateways > Visit Apps & Integrations** and search for Mopé, or, prior to WHMCS 8.6, **Setup > Payments > Payment Gateways** and choose Mopé from the “All Payment Gateways” tab.

Once activated, you need to enter your credentials in the appropriate boxes, as shown below.

![Mopé setup page](mope-setup.png)

In the **Display Name** box, customise the name to something more friendly such as “Mopé”.

In the **Test API Key** and **Live API Key** boxes, enter the API keys received from the Mopé team. If you want to use test mode, make sure you have entered a Test API Key.

## Features

| Type             | One Time | Recurring | Refunds |
|------------------|----------|-----------|---------|
| Mopé App Payment | Yes      | No        | No      |


## Updates
Gateway updates will be released as needed, including new features, bug fixes and security enhancements.

## FAQ

<details>
<summary>Can I copy, modify or redistribute this payment gateway?</summary>
 This module is open source and MIT licensed, such that you may copy, modify or redistribute this payment gateway as you wish.
</details>

<details>
<summary>Is Mopé available in all countries?</summary>
 Currently Mopé works for users in Suriname and the Netherlands.
</details>