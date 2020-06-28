<?php

namespace Cognito\PayumBraintree\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Request\Capture;
use Cognito\PayumBraintree\Request\Api\ObtainNonce;

class CaptureAction implements ActionInterface, GatewayAwareInterface {
    use GatewayAwareTrait;

    private $config;

    /**
     * @param string $templateName
     */
    public function __construct(ArrayObject $config) {
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     *
     * @param Capture $request
     */
    public function execute($request) {
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());
        if ($model['status']) {
            return;
        }

        $model['braintreeGateway'] = new \Braintree\Gateway([
            'environment' => $this->config['sandbox'] ? 'sandbox' : 'production',
            'merchantId' => $this->config['merchantId'],
            'publicKey' => $this->config['publicKey'],
            'privateKey' => $this->config['privateKey'],
        ]);

        $obtainNonce = new ObtainNonce($request->getModel());
        $obtainNonce->setModel($model);

        $this->gateway->execute($obtainNonce);

        if (!$model->offsetExists('status')) {
            // Create transaction
            $transactionResult = $model['braintreeGateway']->transaction()->sale([
                'amount' => $model['amount'],
                'paymentMethodNonce' => $model['nonce'],
                //'deviceData' => $deviceDataFromTheClient,
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);
            if ($transactionResult->success) {
                // Report successful
                $model['status'] = 'success';
            } else {
                // Report error
                $model['status'] = 'failed';
                $model['error'] = 'failed';
            }
            $model['transactionReference'] = $transactionResult->transaction->id;
            $model['result'] = $transactionResult->transaction;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request) {
        return
            $request instanceof Capture &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
