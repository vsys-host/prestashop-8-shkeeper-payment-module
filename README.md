# prestashop-8-shkeeper-payment-module
SHKeeper payment gateway plugin for PrestaShop

*Module has been tested on CMS Prestashop 8.1.7*

## Installation
### Upload via Module Manager

Download from [Github releases page](https://github.com/vsys-host/prestashop-8-shkeeper-payment-module/releases) the latest module archive `prestashop-8-shkeeper-payment-module.zip`
* Upload `prestashop-8-shkeeper-payment-module.zip` to your PrestaShop installation using the administrator menu _Modules_ -> _Module Manager_ -> _Upload a module_
* Configure the module (_Configure_)

### Manual Module Installation

In rare cases, you may need to install a module by manually transferring the files onto the server. This is recommended only when absolutely necessary, for example when your server is not configured to allow automatic installations.

This procedure requires you to be familiar with the process of transferring files using an SFTP client. It is recommended for advanced users and developers.

Detailed instruction can be found on official PrestaShop [site](https://addons.prestashop.com/en/content/13-installing-modules)
## Configuration

After successful installation you should configure module. At the payment module configuration page:
1. Enter the api key, api url, instructions for your customers, and that all.
    * Instruction – Contains the explanation on how to pay by SHKeeper.
    * Api key - Authorization and identification SHKeeper key. You can generate it in SHKeeper admin panel for any crypto wallet.
    * Api url - SHKeeper server api entry point.
2. Once done save the changes.

## You are done!

## Testing

You can use our demo SHKeeper installation to test module with your PrestaShop. SHKeeper demo version working in a Testnet network, do not use it for real payments.
SHKeeper demo version is available from us, so you can try it yourself without installing it:

[SHKeeper demo](https://demo.shkeeper.io/)

**Login:** admin

**Password:** admin  
<p align="center">
  <img src="https://github.com/user-attachments/assets/3106ddd6-552b-4b49-89a6-6ae1ea1b2a03" alt="image">
</p>

<p align="center">
  <img src="https://github.com/user-attachments/assets/84d1b943-ec07-4a39-8314-a2ce52c3b97b" alt="image3">
</p>

<p align="center">
  <img src="https://github.com/user-attachments/assets/398ead16-5db5-41ff-8e42-80cc5537ed42" alt="image1">
</p>

<p align="center">
  <img src="https://github.com/user-attachments/assets/3f8204fa-6b22-4cc3-a550-7e79601b906e" alt="image2">
</p>





