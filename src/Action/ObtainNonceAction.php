<?php

namespace Cognito\PayumBraintree\Action;

use Cognito\PayumBraintree\Request\Api\ObtainNonce;
use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\LogicException;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Reply\HttpResponse;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\RenderTemplate;

class ObtainNonceAction implements ActionInterface, GatewayAwareInterface {
    use GatewayAwareTrait;


    /**
     * @var string
     */
    protected $templateName;

    /**
     * @param string $templateName
     */
    public function __construct(string $templateName) {
        $this->templateName = $templateName;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($request) {
        /** @var $request ObtainNonce */
        RequestNotSupportedException::assertSupports($this, $request);

        $model = ArrayObject::ensureArrayObject($request->getModel());

        if ($model['card']) {
            throw new LogicException('The token has already been set.');
        }

        $getHttpRequest = new GetHttpRequest();
        $this->gateway->execute($getHttpRequest);
        if ($getHttpRequest->method == 'POST' && isset($getHttpRequest->request['payment_method_nonce'])) {
            $model['nonce'] = $getHttpRequest->request['payment_method_nonce'];

            return;
        }

        $email = $model['local']['email'];

        // Search for customer
        $customer_id = md5($email);
        try {
            $customer = $model['braintreeGateway']->customer()->find($customer_id);
        } catch (\Exception $e) {
            $result = $model['braintreeGateway']->customer()->create([
                'id' => $customer_id,
                'email' => $email,
            ]);
        }
        //dump($BraintreeGateway->clientToken()->generate());exit;
        $clientToken = $model['braintreeGateway']->clientToken()->generate([
            'customerId' => $customer_id,
        ]);
        $this->gateway->execute($renderTemplate = new RenderTemplate($this->templateName, array(
            //'model' => $model,
            'amount' => $model['currencySymbol'] . ' ' . $model['amount'],
            'clientToken' => $clientToken,
            'actionUrl' => $getHttpRequest->uri,
        )));

        throw new HttpResponse($renderTemplate->getResult());
    }

    /**
     * {@inheritDoc}
     */
    public function supports($request) {
        return
            $request instanceof ObtainNonce &&
            $request->getModel() instanceof \ArrayAccess;
    }
}
