<?php

/**
 *                  ___________       __            __
 *                  \__    ___/____ _/  |_ _____   |  |
 *                    |    |  /  _ \\   __\\__  \  |  |
 *                    |    | |  |_| ||  |   / __ \_|  |__
 *                    |____|  \____/ |__|  (____  /|____/
 *                                              \/
 *          ___          __                                   __
 *         |   |  ____ _/  |_   ____ _______   ____    ____ _/  |_
 *         |   | /    \\   __\_/ __ \\_  __ \ /    \ _/ __ \\   __\
 *         |   ||   |  \|  |  \  ___/ |  | \/|   |  \\  ___/ |  |
 *         |___||___|  /|__|   \_____>|__|   |___|  / \_____>|__|
 *                  \/                           \/
 *                  ________
 *                 /  _____/_______   ____   __ __ ______
 *                /   \  ___\_  __ \ /  _ \ |  |  \\____ \
 *                \    \_\  \|  | \/|  |_| ||  |  /|  |_| |
 *                 \______  /|__|    \____/ |____/ |   __/
 *                        \/                       |__|
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) 2015 Total Internet Group B.V. (http://www.tig.nl)
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\Buckaroo\Gateway\Http\TransactionBuilder;

use Magento\Store\Model\ScopeInterface;

abstract class AbstractTransactionBuilder implements \TIG\Buckaroo\Gateway\Http\TransactionBuilderInterface
{
    /**
     * Module supplier.
     */
    const MODULE_SUPPLIER = 'TIG';

    /**
     * Module code.
     */
    const MODULE_CODE = 'TIG_Buckaroo';

    /**#@+
     * Config Xpaths
     */
    const XPATH_PAYMENT_DESCRIPTION = 'buckaroo/advanced/payment_description';
    /**#@-*/

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;

    /**
     * @var array
     */
    protected $_services;

    /**
     * @var string
     */
    protected $_method;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $_productMetadata;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var bool
     */
    protected $_startRecurrent = false;

    /**
     * @var null|string
     */
    protected $_originalTransactionKey = null;

    /**
     * @param null|string $originalTransactionKey
     *
     * @return $this
     */
    public function setOriginalTransactionKey($originalTransactionKey)
    {
        $this->_originalTransactionKey = $originalTransactionKey;

        return $this;
    }

    /**
     * @param boolean $startRecurrent
     *
     * @return $this
     */
    public function setStartRecurrent($startRecurrent)
    {
        $this->_startRecurrent = $startRecurrent;

        return $this;
    }

    /**
     * TransactionBuilder constructor.
     *
     * @param \Magento\Framework\App\ProductMetadataInterface    $productMetadata
     * @param \Magento\Framework\Module\ModuleListInterface      $moduleList
     * @param \Magento\Framework\UrlInterface                    $urlBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_productMetadata = $productMetadata;
        $this->_moduleList = $moduleList;
        $this->_urlBuilder = $urlBuilder;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return $this
     */
    public function setOrder($order)
    {
        $this->_order = $order;

        return $this;
    }

    /**
     * @return array
     */
    public function getServices()
    {
        return $this->_services;
    }

    /**
     * @param array $services
     *
     * @return $this
     */
    public function setServices($services)
    {
        $this->_services = $services;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->_method;
    }

    /**
     * @param string $method
     *
     * @return $this
     */
    public function setMethod($method)
    {
        $this->_method = $method;

        return $this;
    }

    /**
     * @return \TIG\Buckaroo\Gateway\Http\Transaction
     */
    public function build()
    {
        return new \TIG\Buckaroo\Gateway\Http\Transaction($this->getBody(), $this->getHeaders(), $this->getMethod());
    }

    /**
     * @return array
     */
    abstract public function getBody();

    /**
     * @returns array
     */
    public function getHeaders()
    {
        $module = $this->_moduleList->getOne(self::MODULE_CODE);

        $headers[] = new \SoapHeader(
            'https://checkout.buckaroo.nl/PaymentEngine/',
            'MessageControlBlock',
            [
                'Id' => '_control',
                'WebsiteKey' => 'SniACG6eSj',
                'Culture' => 'nl-NL',
                'TimeStamp' => time(),
                'Channel' => 'Web',
                'Software' => [
                    'PlatformName' => $this->_productMetadata->getName()
                                      . ' - '
                                      . $this->_productMetadata->getEdition(),
                    'PlatformVersion' => $this->_productMetadata->getVersion(),
                    'ModuleSupplier' => self::MODULE_SUPPLIER,
                    'ModuleName' => $module['name'],
                    'ModuleVersion' => $module['setup_version'],
                ]
            ],
            false
        );

        $headers[] = new \SoapHeader(
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
            'Security',
            [
                'Signature' => [
                    'SignedInfo' => [
                        'CanonicalizationMethod' => [
                            'Algorithm' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
                        ],
                        'SignatureMethod' => [
                            'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#rsa-sha1',
                        ],
                        'Reference' => [
                            [
                                'Transforms' => [
                                    [
                                        'Algorithm' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
                                    ]
                                ],
                                'DigestMethod' => [
                                    'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                                ],
                                'DigestValue' => '',
                                'URI' => '#_body',
                                'Id' => null,
                            ],
                            [
                                'Transforms' => [
                                    [
                                        'Algorithm' => 'http://www.w3.org/2001/10/xml-exc-c14n#',
                                    ]
                                ],
                                'DigestMethod' => [
                                    'Algorithm' => 'http://www.w3.org/2000/09/xmldsig#sha1',
                                ],
                                'DigestValue' => '',
                                'URI' => '#_control',
                                'Id' => null,
                            ]
                        ]
                    ],
                    'SignatureValue' => '',
                ],
                'KeyInfo' => ' ',
            ],
            false
        );

        return $headers;
    }
}