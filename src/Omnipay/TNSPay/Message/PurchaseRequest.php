<?php

namespace Omnipay\TNSPay\Message;

use DOMDocument;
use SimpleXMLElement;
use Omnipay\Common\Message\AbstractRequest;

/**
 * TNSPay Purchase Request
 */
class PurchaseRequest extends AbstractRequest
{

    /**
     * The TNSPay API Version is periodically updated.
     *
     * Documentation is available at https://secure.na.tnspayments.com/api/documentation/apiDocumentation/rest-json/index.html?locale=en_US
     */
    const TNSPAY_API_VERSION_NUMBER = 19;

    public function getMerchantId()
    {
        return $this->getParameter('merchantId');
    }

    public function setMerchantId($value)
    {
        return $this->setParameter('merchantId', $value);
    }

    public function getPassword()
    {
        return $this->getParameter('password');
    }

    public function setPassword($value)
    {
        return $this->setParameter('password', $value);
    }

    /**
     * Get the data to be sent to TNSPay.
     *
     * @return array
     */
    public function getData()
    {
        $this->validate('amount', 'card', 'transactionId');
        $this->getCard()->validate();

        $data = array(
            'apiOperation'  => 'PAY',
            'sourceOfFunds' => array(
                'type'     => 'CARD',
                'provided' => array(
                    'card' => array(
                        'number'       => $this->getCard()->getNumber(),
                        'expiry'       => array(
                            'month' => $this->getCard()->getExpiryDate('m'),
                            'year'  => $this->getCard()->getExpiryDate('y'),
                        ),
                        'securityCode' => $this->getCard()->getCVV(),
                    ),
                ),
            ),
            'transaction'   => array(
                'amount'    => $this->getAmount(),
                'currency'  => $this->getCurrency(),
                'reference' => $this->getTransactionId(),
            ),
            'order'         => array(
                'reference' => $this->getTransactionId(),
            ),
            'customer'      => array(
                'ipAddress' => $_SERVER['REMOTE_ADDR'],
            ),
        );

        return $data;
    }

    /**
     * TNSPay requires an OrderID and a TransactionID
     *
     * At the moment, only a transaction ID is available from OmniPay, so this
     * driver will use the transaction id for both elements.
     *
     * @todo Determine the mechanism for live and test mode.
     */
    protected function getEndpoint()
    {
        return
            'https://secure.na.tnspayments.com/api/rest/version/' . self::TNSPAY_API_VERSION_NUMBER .
            '/merchant/' . $this->getMerchantId() .
            '/order/' . $this->getTransactionId() .
            '/transaction/' . $this->getTransactionId();
    }

    /**
     * Send the data to TNSPay
     *
     * @param array $data
     * @return \Omnipay\Common\Message\ResponseInterface|Response
     */
    public function sendData($data)
    {
        $json         = json_encode($data);
        $headers      = array(
            'Content-Type' => 'application/json;charset=utf-8',
        );
        $httpResponse = $this->httpClient->put($this->getEndpoint(), $headers, $json)
            ->setAuth('merchant.' . $this->getMerchantId(), $this->getPassword())
            ->send();

        return $this->response = new Response($this, $httpResponse->getBody());
    }

}
