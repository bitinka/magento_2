<?php

namespace Pay\Payment\Controller\Redirect;

use Magento\Framework\Controller\ResultFactory;

class SendAuth extends \Magento\Framework\App\Action\Action {

    protected $om;
    protected $_customerSession;
    protected $_httpClientFactory;

    public function __construct(\Magento\Framework\App\Action\Context $context,
    \Magento\Customer\Model\Session $customerSession,
    \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory)
        {
            $this->om =   \Magento\Framework\App\ObjectManager::getInstance();
            $this->_customerSession = $customerSession;
            $this->_httpClientFactory = $httpClientFactory;
            return parent::__construct($context);
        }

    public function execute()
    {

      $ClientId = $this->ClientKey('payment/payu/Client_Id');
      $url = $this->ClientKey('payment/payu/url');
      $key = $this->ClientKey('payment/payu/Key');

          if($this->_customerSession->getAccess_token() == NULL || $this->_customerSession->getAccess_token() == '') {
            	$query = http_build_query([
                  'client_id' => $ClientId,
                  'redirect_uri' => $url.'/payment/Redirect/GetTokenAuth',
                  'response_type' => 'code',
                  'scope' => '',
              ]);
               header( "refresh:0;url=http://www.servidor_autenticacion.com/oauth/authorize?".$query);
          }else{
              $client = $this->_httpClientFactory->create();
              $client->setUri('http://localhost/servidor_autenticacion/public/api/verifytoken');
              $client->setHeaders(array(
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$this->_customerSession->getAccess_token()
              ));

              try {
                $responseBody = $client->request(\Magento\Framework\HTTP\ZendClient::GET)->getBody();
                $oauth = json_decode($responseBody);

                if(!isset($oauth->error)){
                      $this->_redirect('payment/Redirect/SendOrder');
                }else{
                  $client = $this->_httpClientFactory->create();
                  $client->setUri('http://localhost/servidor_autenticacion/public/api/verifytoken');
                  $client->setHeaders(array(
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer '.$this->_customerSession->getRefresh_token()
                  ));

                  try {
                    $responseBody = $client->request(\Magento\Framework\HTTP\ZendClient::GET)->getBody();
                    $oauth = json_decode($responseBody);

                    if(!isset($oauth->error)){
                      $client = $this->_httpClientFactory->create();
                      $client->setUri('http://localhost/servidor_autenticacion/public/oauth/token');
                      $client->setParameterPost(array(
                              'grant_type' => 'refresh_token',
                              'refresh_token' => $this->_customerSession->getRefresh_token(),
                              'client_id' => $ClientId,
                              'client_secret' => $key
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
                            $this->_redirect('payment/Redirect/GetCoins');
                    }else{

                      echo "<script type='text/javascript'>
                              alert('no valid token');</script>";
                      $this->_customerSession->setType('');
                      $this->_customerSession->setAccess_token('');
                      $this->_customerSession->setRefresh_token('');
                      $this->_customerSession->setExpire('');
                      $this->_redirect('payment/Redirect/SendAuth');
                }
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

          }

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
