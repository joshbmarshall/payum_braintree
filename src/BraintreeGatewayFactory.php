<?php
namespace Cognito\PayumBraintree;

use Cognito\PayumBraintree\Action\ConvertPaymentAction;
use Cognito\PayumBraintree\Action\CaptureAction;
use Cognito\PayumBraintree\Action\ObtainNonceAction;
use Cognito\PayumBraintree\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class BraintreeGatewayFactory extends GatewayFactory
{
    /**
     * {@inheritDoc}
     */
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'payum.factory_name' => 'braintree',
            'payum.factory_title' => 'braintree',

            'payum.template.obtain_nonce' => "@PayumBraintree/Action/obtain_nonce.html.twig",

            'payum.action.capture' => function (ArrayObject $config) {
                return new CaptureAction($config);
            },
            'payum.action.status' => new StatusAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.obtain_nonce' => function (ArrayObject $config) {
                return new ObtainNonceAction($config['payum.template.obtain_nonce']);
            },
        ]);

        if (false == $config['payum.api']) {
            $config['payum.default_options'] = array(
                'sandbox' => true,
            );
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [];

            $config['payum.api'] = function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api((array) $config, $config['payum.http_client'], $config['httplug.message_factory']);
            };
        }
        $payumPaths = $config['payum.paths'];
        $payumPaths['PayumBraintree'] = __DIR__ . '/Resources/views';
        $config['payum.paths'] = $payumPaths;
    }
}
