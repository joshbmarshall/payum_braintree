<?php

namespace Cognito\PayumBraintree\Request\Api;

use Payum\Core\Request\Generic;

class ObtainNonce extends Generic {
    protected $response;

    public function getResponse() {
        return $this->response;
    }

    public function setResponse($value) {
        $this->response = $value;
    }
}
