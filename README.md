# Magento 2 Two Factor Auth

![Magento 2 Two Factor Auth](https://i.imgur.com/ykAomLL.png)

It's a magento 2 module to enable two factor authentication for customer to secure their login step.

## How to install?

#### Via Composer

If you try to install via composer, just require your project to the module by running this command :

```
composer require fiko/magento2-customer-two-factor-auth
```

#### Manually

1. Download this repo
2. Create a Directory `app/code/Fiko/CustomerTwoFactorAuth`
3. Copy downloaded repo to this directory

Once you download it (both composer or manually), just run this commands to apply this module to your project :

```
php bin/magento setup:upgrade --keep-generated
php bin/magento setup:di:compile
```

## How to use?

### Customer Guide

#### 1. Enable Two Factor Authentication

1. Login with customer account.
2. Go to my account.
3. Go to Account Security on sidebar.
4. Enable Login Security.
5. Scan the QR Code.
6. Confirm the code.
7. Try to logout and login back again.

#### 2. Disable Two Factor Authentication

1. Login with customer account.
2. Validate the OTP.
3. Go to my account.
4. Go to Account Security on sidebar.
5. Disable Login Security.
6. Input current password.
7. Try to logout and login back again.

### Admin Guide

#### 1. Generate Secret Key

1. Login onto adminhtml.
2. Customers > All Customers.
3. Edit one of the customer.
4. Click `Generate 2FA Secret Key`.
5. Go to `Account Information` tab.
6. See field of `2FA Secret Key`.

#### 2. Enable Two Factor Authentication

1. Login onto adminhtml.
2. Customers > All Customers.
3. Edit one of the customer.
5. Go to `Account Information` tab.
6. Check `Enable 2FA` field (make sure the `2FA Secret Key` is not empty.
7. Save the secret key and setup it on your authenticator app.
7. Save and try login.

#### 3. Disable Two Factor Authentication

1. Login onto adminhtml.
2. Customers > All Customers.
3. Edit one of the customer.
5. Go to `Account Information` tab.
6. Check `Disable 2FA` field.
7. Save and try login.

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

[MIT](https://choosealicense.com/licenses/mit/) &copy; 2022