# Braintree Payment Module

The Payum extension to purchase through Braintree

## Install and Use

To install, it's easiest to use composer:

    composer require cognito/payum_braintree

### Build the config

```php
<?php

use Payum\Core\PayumBuilder;
use Payum\Core\GatewayFactoryInterface;

$defaultConfig = [];

$payum = (new PayumBuilder)
    ->addGatewayFactory('braintree', function(array $config, GatewayFactoryInterface $coreGatewayFactory) {
        return new \Cognito\PayumBraintree\BraintreeGatewayFactory($config, $coreGatewayFactory);
    })

    ->addGateway('braintree', [
        'factory' => 'braintree',
        'merchantId' => 'Your merchant Id',
        'publicKey' => 'Your Public Key',
        'privateKey' => 'Your Private Key',
        'sandbox' => true,
    ])

    ->getPayum()
;
```

### Request payment

```php
<?php

use Payum\Core\Request\Capture;

$storage = $payum->getStorage(\Payum\Core\Model\Payment::class);
$request = [
    'invoice_id' => 100,
];

$payment = $storage->create();
$payment->setNumber(uniqid());
$payment->setCurrencyCode($currency);
$payment->setTotalAmount(100); // Total cents
$payment->setDescription(substr($description, 0, 45));
$payment->setDetails([
        'local' => [
        'email' => $email, // Used for the customer to be able to save payment details
    ],
]);
$storage->setInternalDetails($payment, $request);

$captureToken = $payum->getTokenFactory()->createCaptureToken('braintree', $payment, 'done.php');
$url = $captureToken->getTargetUrl();
header("Location: " . $url);
die();
```

### Check it worked

```php
<?php
/** @var \Payum\Core\Model\Token $token */
$token = $payum->getHttpRequestVerifier()->verify($request);
$gateway = $payum->getGateway($token->getGatewayName());

/** @var \Payum\Core\Storage\IdentityInterface $identity **/
$identity = $token->getDetails();
$model = $payum->getStorage($identity->getClass())->find($identity);
$gateway->execute($status = new GetHumanStatus($model));

/** @var \Payum\Core\Request\GetHumanStatus $status */

// using shortcut
if ($status->isNew() || $status->isCaptured() || $status->isAuthorized()) {
	// success
} elseif ($status->isPending()) {
	// most likely success, but you have to wait for a push notification.
} elseif ($status->isFailed() || $status->isCanceled()) {
	// the payment has failed or user canceled it.
}
```

## License

Payum Braintree is released under the [MIT License](LICENSE).
