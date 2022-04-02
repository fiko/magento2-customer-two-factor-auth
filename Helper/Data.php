<?php

/**
 * Copyright © Fiko Borizqy, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fiko\CustomerTwoFactorAuth\Helper;

use Base32\Base32;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use OTPHP\TOTP;

class Data extends AbstractHelper
{
    const TOTP_SECRET = 'totp_secret';
    const IS_ENABLE = 'is_totp_enable';
    const OTP_SESSION = 'fiko_otp_login';

    public function __construct(
        Context $context,
        Session $session,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context);

        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->data = $data;
    }

    /**
     * Setup OTP login session of current customer.
     */
    public function getSessionOtpLogin(): ?array
    {
        return $this->session->getData(self::OTP_SESSION);
    }

    /**
     * Setup OTP login session of the customer.
     *
     * @param int    $customerId Customer ID
     * @param string $username   it can be email address of the customer
     * @param string $password   password submited by customer
     */
    public function setSessionOtpLogin(int $customerId, string $username, string $password): void
    {
        $this->session->setData(self::OTP_SESSION, [
            'customer_id' => $customerId,
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * Removing OTP Login session.
     */
    public function unsetSessionOtpLogin(): void
    {
        $this->session->unsetData(self::OTP_SESSION);
    }

    /**
     * Generate random secret.
     *
     * @throws Exception
     */
    public function generateSecret(): string
    {
        $secret = random_bytes(16);
        // seed for iOS devices to avoid errors with barcode
        $seed = 'abcd';

        return preg_replace('/[^A-Za-z0-9]/', '', Base32::encode($seed.$secret));
    }

    /**
     * Check does the customer enabling OTP Login
     *
     * @param Magento\Customer\Model\Data\Customer $customer Customer object data
     *
     * @return int string of the code or null
     */
    public function isCustomerOtpEnable(Customer $customer): bool
    {
        $isEnable = $customer->getCustomAttribute(self::IS_ENABLE);

        return !is_null($isEnable) ? (bool) $isEnable->getValue() : false;
    }

    /**
     * Get Customer Secret code to generate TOTP.
     *
     * @param Magento\Customer\Model\Data\Customer $customer Customer object data
     *
     * @return string|null string of the code or null
     */
    public function getCustomerOtpSecret(Customer $customer): ?string
    {
        $attribute = $customer->getCustomAttribute(self::TOTP_SECRET);

        return !is_null($attribute) ? $attribute->getValue() : null;
    }

    /**
     * get customer TOTP Object.
     *
     * @param Customer $customer Customer object
     */
    public function getCustomerOtp(Customer $customer): TOTP
    {
        $otpSecret = $this->getCustomerOtpSecret($customer);

        return TOTP::create($otpSecret);
    }

    /**
     * Verify submited code by Customer to verify TOTP.
     *
     * @param string   $otpCode  OTP submited by customer
     * @param Customer $customer Customer object
     */
    public function verifyCustomerOtp($otpCode, Customer $customer): bool
    {
        $otp = $this->getCustomerOtp($customer);

        return $otp->verify($otpCode) ? true : false;
    }

    /**
     * Get TFA provisioning URL.
     *
     * @throws NoSuchEntityException
     */
    public function getProvisioningUrl(Customer $customer): string
    {
        $websiteName = $this->storeManager->getStore()->getWebsite()->getName();

        $totp = $this->getCustomerOtp($customer);
        $totp->setLabel($customer->getEmail());
        $totp->setIssuer($websiteName);

        return $totp->getProvisioningUri();
    }

    /**
     * Render TFA QrCode.
     *
     * @param Magento\Customer\Model\Data\Customer $customer Customer object data
     *
     * @return string string of the image
     */
    public function getQrCodeAsPng(Customer $customer): string
    {
        $qrCode = new QrCode($this->getProvisioningUrl($customer));
        $qrCode->setSize(400);
        $qrCode->setMargin(0);
        $qrCode->setErrorCorrectionLevel(ErrorCorrectionLevel::HIGH());
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLabelFontSize(16);
        $qrCode->setEncoding('UTF-8');

        $writer = new PngWriter();

        return $writer->writeString($qrCode);
    }
}
