<?php

namespace Pay\Payment\Controller\Redirect;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Config\Source\Order\Status;

class SendMail extends \Magento\Framework\App\Action\Action
{


protected $_customerSession;
protected $_order;
protected $om;

public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\Order $order
      ) {
        parent::__construct($context);
        $this->_customerSession = $customerSession;
        $this->_order = $order;
        $this->om =   \Magento\Framework\App\ObjectManager::getInstance();
      }

public function execute()
    {
        $ordData = $this->_order->load($_POST['nOrder']);
        if($_POST['code'] == 0){
          $ordData->setState('complete', true);
          $ordData->setStatus('complete', true);
          $payment = $ordData->getPayment();
          $payment->setMethod('inkapay', true);
          $payment->save();
        }else{
          $ordData->setState('pending_payment', true);
          $ordData->setStatus('pending_payment', true);
          $payment = $ordData->getPayment();
          $payment->setMethod('inkapay', true);
          $payment->save();
        }

         try {
            $ordData->save();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        }


        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($_POST['nOrder']);
        $email = $order->getCustomerEmail();
        if ($order) {
            try {
                $this->_objectManager->create('\Magento\Sales\Model\OrderNotifier')
                    ->notify($order);
                $this->messageManager->addSuccess(__('Your order are procesad.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t send the email order right now.'));
                $this->_objectManager->create('Magento\Sales\Model\OrderNotifier')->critical($e);
            }
        }
        $this->_redirect('customer/account/login');
    }

}
