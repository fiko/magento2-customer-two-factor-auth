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
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\StoreManagerInterface;
use OTPHP\TOTP;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    const TOTP_SECRET = 'totp_secret';
    const IS_ENABLE = 'is_totp_enable';
    const OTP_SESSION = 'fiko_otp_login';
    const QRCODE_VALIDATION = 'qr_code_validation';
    const ENABLING_2FA = '2fa_enabling';
    const ACL_GENERATE_SECRET_KEY = 'Fiko_CustomerTwoFactorAuth::generate_secret_key';

    public function __construct(
        Context $context,
        Session $session,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($context);

        $this->session = $session;
        $this->storeManager = $storeManager;
        $this->data = $data;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
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
     * @param bool   $isReload   is it a reloaded request
     */
    public function setSessionOtpLogin(
        int $customerId,
        string $username,
        string $password,
        bool $isReload = false
    ): void {
        $this->session->setData(self::OTP_SESSION, [
            'is_reload' => $isReload,
            'customer_id' => $customerId,
            'username' => $username,
            'password' => $password,
        ]);
    }

    /**
     * set Reload Page to be true or false.
     */
    public function setReloadPage(bool $isReload): void
    {
        $otpSession = $this->getSessionOtpLogin();
        $otpSession['is_reload'] = $isReload;
        $this->session->setData(self::OTP_SESSION, $otpSession);
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
     * Check does the customer enabling OTP Login.
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
     * @return string|null string of the code or null
     */
    public function getCustomerOtpSecret(?Customer $customer = null): ?string
    {
        $customer = $customer ?: $this->getCustomer();
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
     * @param string        $otpToken token submitted by customer
     * @param Customer|null $customer Customer object
     * @param string        $otpToken OTP submited by customer
     *
     * @return bool is it succeed or failed on OTP verification
     */
    public function verifyCustomerOtp($otpToken, ?Customer $customer = null): bool
    {
        $customer = $customer ?: $this->getCustomer();

        $otp = $this->getCustomerOtp($customer);

        return $otp->verify($otpToken) ? true : false;
    }

    /**
     * Get TFA provisioning URL.
     *
     * @throws Exception
     */
    public function getProvisioningUrl(): string
    {
        try {
            $websiteName = $this->storeManager->getStore()->getWebsite()->getName();
            $customer = $this->getCustomer();

            $totp = $this->getCustomerOtp($customer);
            $totp->setLabel($customer->getEmail());
            $totp->setIssuer($websiteName);

            return $totp->getProvisioningUri();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Render TFA QrCode.
     *
     * @return string string of the image
     */
    public function getQrCodeAsPng(): string
    {
        $qrCode = new QrCode($this->getProvisioningUrl($this->getCustomer()));
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

    /**
     * Getting customer object.
     *
     * @param int $customerId customer Id which will be loaded (default: null)
     *
     * @return Magento\Customer\Api\Data\CustomerInterface
     */
    public function getCustomer($customerId = null): ?CustomerInterface
    {
        try {
            $customerId = $customerId ?: $this->session->getCustomerId();

            return $this->customerRepository->getById($customerId);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());

            return null;
        }
    }

    /**
     * check whether customer OTP is enable or not.
     *
     * @param Customer|null $customer customer object or data
     *
     * @return bool status of the customer OTP/2FA
     */
    public function isOtpEnabled(?Customer $customer = null): bool
    {
        try {
            $customer = $customer ?: $this->getCustomer();
            $attr = $customer->getCustomAttribute(self::IS_ENABLE);
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());

            return false;
        }

        return $attr ? (bool) $attr->getValue() : false;
    }
}
