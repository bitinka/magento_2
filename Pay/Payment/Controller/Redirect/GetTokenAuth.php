<?php

namespace Pay\Payment\Controller\Redirect;


class GetTokenAuth extends \Magento\Framework\App\Action\Action {

    protected $_httpClientFactory;
    protected $request;
    protected $_customerSession;
    protected $om;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Customer\Model\Session $customerSession,
         \Magento\Framework\App\Request\Http $request)
    {
        $this->_httpClientFactory = $httpClientFactory;
        $this->request = $request;
        $this->_customerSession = $customerSession;
        $this->om =   \Magento\Framework\App\ObjectManager::getInstance();
        return parent::__construct($context);
    }

    public function execute()
    {
            if(isset($_GET['code'])){

                $ClientId = $this->ClientKey('payment/payu/Client_Id');
                $key = $this->ClientKey('payment/payu/Key');
                $url = $this->ClientKey('payment/payu/url');

                $client = $this->_httpClientFactory->create();
                $client->setUri('http://localhost/servidor_autenticacion/public/oauth/token');
                $client->setParameterPost(array(
                        'grant_type' => 'authorization_code',
                                    'client_id' => $ClientId,
                                    'client_secret' => $key,
                                    'redirect_uri' => $url.'/payment/Redirect/GetTokenAuth',
                                    'code' => $_GET['code']
                    ));

                try {
                    $responseBody = $client->request(\Magento\Framework\HTTP\ZendClient::POST)->getBody();
                    $oauth = json_decode($responseBody);

                    $this->_customerSession->setType($oauth->token_type);
                    $this->_customerSession->setAccess_token($oauth->access_token);
                    $this->_customerSession->setRefresh_token($oauth->refresh_token);
                    $this->_customerSession->setExpire($oauth->expires_in);

                    $this->_redirect('payment/Redirect/SendOrder');

                } catch (Zend_Http_Client_Adapter_Exception $e) {
                    echo "<script type='text/javascript'>
                            alert('Error al enviar solicitud');
                            alert('".$e->getMessage()."');
                            window.location.href = '".$url."';
                          </script>";
                } catch (Zend_Some_other_Exception $e) {
                    echo "<script type='text/javascript'>
                            alert('Error al enviar solicitud');
                            alert('".$e->getMessage()."');
                            window.location.href = '".$url."';
                          </script>";
                } catch (Exception $e) {
                    echo "<script type='text/javascript'>
                            alert('Error al enviar solicitud');
                            alert('".$e->getMessage()."');
                            window.location.href = '".$url."';
                          </script>";
                }

             $this->_redirect('payment/Redirect/SendOrder');

            }else{
                echo "<script type='text/javascript'>
                        alert('".$_GET["error"].$_GET["state"]."');
                        window.location.href = '".$url."';
                      </script>";
            }
    }

    protected function ClientKey($configPath)
        {
            $scopeConfig = $this->om->create('Magento\Framework\App\Config\ScopeConfigInterface');
            $value =  $scopeConfig->getValue(
            $configPath,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            return $value;
        }

}
