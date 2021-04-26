<?php

/*
 * This file is part of itk-dev/edoc-api.
 *
 * (c) 2018–2019 ITK Development
 *
 * This source file is subject to the MIT license.
 */

namespace ItkDev\Edoc\Util;

// @see https://github.com/WsdlToPhp/PackageGenerator/issues/57#issuecomment-222619148
class EdocClient extends \SoapClient
{
    private $username;
    private $password;

    private $lastRequestHeaders;

    public function __construct($wsdl, array $options = [])
    {
        // Accessing the wsdl requires authentication, so we user a local wsdl.
        parent::__construct(__DIR__.'/wsdl/edoc.asmx.wsdl', $options);
        $this->username = $options['username'] ?? null;
        $this->password = $options['password'] ?? null;
    }

    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $headers = [
            'Method: POST',
            'Connection: Keep-Alive',
            'User-Agent: PHP-SOAP-CURL',
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "'.$action.'"',
        ];

        $this->lastRequestHeaders = $headers;
        $ch = curl_init($location);
        curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, \CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, \CURLOPT_POST, true);
        curl_setopt($ch, \CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, \CURLOPT_HTTP_VERSION, \CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, \CURLOPT_HTTPAUTH, \CURLAUTH_NTLM);
        curl_setopt($ch, \CURLOPT_USERPWD, $this->username.':'.$this->password);
        $response = curl_exec($ch);

        return $response;
    }

    public function __getLastRequestHeaders()
    {
        return implode("\n", $this->lastRequestHeaders).'\n';
    }
}
