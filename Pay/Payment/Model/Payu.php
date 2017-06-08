<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Pay\Payment\Model;



/**
 * Pay In Store payment method model
 */
class Payu extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'payu';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;
}
