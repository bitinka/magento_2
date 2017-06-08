<?php

namespace Pay\Payment\Controller\Redirect;

class SendOrder extends \Magento\Framework\App\Action\Action {

  protected $_checkoutSession;
    protected $_customerSession;
    protected $_orderFactory;
    protected $om;
    protected $order;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order $order
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderFactory = $orderFactory;
        $this->_order = $order;
        $this->om =   \Magento\Framework\App\ObjectManager::getInstance();
        $this->_customerSession = $customerSession;
        return parent::__construct($context);
    }



    public function execute() {
            $nOrder = $this->_getOrder()->getId();
            $order = $this->om->create('Magento\Sales\Model\Order')->load($nOrder);
            $orderItems = $order->getAllItems();

            $total = 0;

            //detail order
            foreach ($orderItems as $item)
            {
                $total = $total + $item['base_row_total_incl_tax'];
            }
            $ordData = $this->_order->load($nOrder);
            $payment = $ordData->getPayment();
            $payment->setMethod('inkapay', true);
            $payment->save();
            $ordData->save();
            $this->_sendForm($nOrder,$total);
}

    protected function _getOrder()
    {
        $orderIncrementId = $this->_checkoutSession->getLastRealOrderId();
        $order = $this->_orderFactory->create()->loadByIncrementId($orderIncrementId);
        return $order;
    }

    protected function _sendForm($nOrder,$total)
    {
        echo '<form action="http://127.0.0.1/inkapay/ecommerce/coins" method="post" name="formulario1">
            <div class="form-group">
              <input type="hidden" name="nOrder" value="'.$nOrder.'">
            </div>
            <div class="form-group">
              <input type="hidden" name="create" value="'.date("d/m/Y").'">
            </div>
            <div class="form-group">
              <input type="hidden" name="total" value="'.$total.'">
            </div>
            <div class="form-group">
              <input type="hidden" name="token" value="'.$this->_customerSession->getAccess_token().'">
            </div>
        </form>';
        echo '<script type="text/javascript">
          document.formulario1.submit();
        </script>';
    }
}
