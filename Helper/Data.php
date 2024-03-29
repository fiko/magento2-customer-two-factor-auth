<?php
/**
 * Copyright © Fiko Borizqy. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fiko\CustomerTwoFactorAuth\Helper;

use Base32\Base32;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Validator\Exception;
use Magento\Store\Model\StoreManagerInterface;
use OTPHP\TOTP;
use Psr\Log\LoggerInterface;

class Data extends AbstractHelper
{
    public const TOTP_SECRET = 'totp_secret';
    public const IS_ENABLE = 'is_totp_enable';
    public const OTP_SESSION = 'fiko_otp_login';
    public const QRCODE_VALIDATION = 'qr_code_validation';
    public const ENABLING_2FA = '2fa_enabling';
    public const ACL_GENERATE_SECRET_KEY = 'Fiko_CustomerTwoFactorAuth::generate_secret_key';

    /** @var Session */
    public $session;

    /** @var StoreManagerInterface */
    public $storeManager;

    /** @var CustomerRepositoryInterface */
    public $customerRepository;

    /** @var LoggerInterface */
    public $logger;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $session
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepositoryInterface $customerRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        StoreManagerInterface $storeManager,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);

        $this->session = $session;
        $this->storeManager = $storeManager;
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
     * Set Reload Page to be true or false.
     *
     * @param bool $isReload is the current page is refreshed or not
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
     */
    public function generateSecret(): string
    {
        $secret = random_bytes(16);
        // seed for iOS devices to avoid errors with barcode
        $seed = 'abcd';

        return preg_replace('/[^A-Za-z0-9]/', '', Base32::encode($seed . $secret));
    }

    /**
     * Check does the customer enabling OTP Login.
     *
     * @param Customer $customer Customer object data
     *
     * @return int string of the code or null
     */
    public function isCustomerOtpEnable(Customer $customer): bool
    {
        $isEnable = $customer->getCustomAttribute(self::IS_ENABLE);

        return $isEnable !== null ? (bool) $isEnable->getValue() : false;
    }

    /**
     * Get Customer Secret code to generate TOTP.
     *
     * @param Customer $customer Customer object data
     *
     * @return string|null string of the code or null
     */
    public function getCustomerOtpSecret(?Customer $customer = null): ?string
    {
        $customer = $customer ?: $this->getCustomer();
        $attribute = $customer->getCustomAttribute(self::TOTP_SECRET);

        return $attribute !== null ? $attribute->getValue() : null;
    }

    /**
     * Get customer TOTP Object.
     *
     * @param Customer $customer Customer object data
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
        // @codingStandardsIgnoreStart
        $qrCode = new QrCode($this->getProvisioningUrl($this->getCustomer()));
        $qrCode->setSize(400);
        $qrCode->setMargin(0);

        // Endoird QRCode version 4.0.0
        if (class_exists(\Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh::class)) {
            $qrCode->setErrorCorrectionLevel(new \Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh());
            $qrCode->setForegroundColor(new \Endroid\QrCode\Color\Color(0, 0, 0, 0));
            $qrCode->setBackgroundColor(new \Endroid\QrCode\Color\Color(255, 255, 255, 0));
            $qrCode->setEncoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'));

            $writer = new PngWriter();
            $pngData = $writer->write($qrCode);

            return $pngData->getString();
        }

        // Endroid version 3
        $qrCode->setErrorCorrectionLevel(\Endroid\QrCode\ErrorCorrectionLevel::HIGH());
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0]);
        $qrCode->setBackgroundColor(['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0]);
        $qrCode->setLabelFontSize(16);
        $qrCode->setEncoding('UTF-8');

        $writer = new PngWriter();

        return $writer->writeString($qrCode);
        // @codingStandardsIgnoreEnd
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
     * Check whether customer OTP is enable or not.
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
