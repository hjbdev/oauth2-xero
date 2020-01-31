<?php

namespace Calcinai\OAuth2\Client\Provider;

use Calcinai\OAuth2\Client\Provider\Exception\XeroProviderException;
use Calcinai\OAuth2\Client\XeroResourceOwner;
use Calcinai\OAuth2\Client\XeroTenant;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

/**
 * @package Calcinai\OAuth2\Client\Provider
 */
class Xero extends AbstractProvider
{
    const METHOD_DELETE = 'DELETE';

    /**
     * Returns the base URL for authorizing a client.
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://login.xero.com/identity/connect/authorize';
    }

    /**
     * Returns the base URL for requesting an access token.
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://identity.xero.com/connect/token';
    }

    /**
     * @param array $params
     * @return string
     */
    public function getTenantsUrl(array $params = null)
    {
        if ($params) {
            $params = '?' . http_build_query($params);
        }

        return 'https://api.xero.com/connections' . $params;
    }

    /**
     * @param AccessToken $token
     * @param array $params
     * @return XeroTenant[]
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     * @throws \Exception
     */
    public function getTenants(AccessToken $token, array $params = null)
    {
        $request = $this->getAuthenticatedRequest(
            self::METHOD_GET,
            $this->getTenantsUrl($params),
            $token
        );

        $response = $this->getParsedResponse($request);
        $tenants = [];

        foreach ($response as $tenantData) {
            $tenants[] = XeroTenant::fromArray($tenantData);
        }

        return $tenants;
    }

    /**
     * @param AccessToken $token
     * @param $connectionId
     * @return mixed
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    public function disconnect(AccessToken $token, $connectionId)
    {
        $url = sprintf('%s/%s', $this->getTenantsUrl(), $connectionId);

        $request = $this->getAuthenticatedRequest(self::METHOD_DELETE, $url, $token);

        $response = $this->getParsedResponse($request);
        return $response;
    }


    /**
     * Returns the URL for requesting the resource owner's details.
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        //This does not exist as it comes down in the JWT
        return '';
    }

    /**
     * @param AccessToken $token
     * @return XeroResourceOwner
     */
    public function getResourceOwner(AccessToken $token)
    {
        return XeroResourceOwner::fromJWT($token->getValues()['id_token']);
    }


    /**
     * Checks a provider response for errors.
     *
     * @param ResponseInterface $response
     * @param array|string $data Parsed response data
     *
     * @throws \Calcinai\OAuth2\Client\Provider\Exception\XeroProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new XeroProviderException(
                $data['error'] ?: $response->getReasonPhrase(),
                $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * @return array
     */
    protected function getDefaultScopes()
    {
        return ['openid email profile'];
    }

    /**
     * Generates a resource owner object from a successful resource owner
     * details request.
     *
     * @param  array $response
     * @param  AccessToken $token
     * @return void|ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        // This does nothing as we get the resource owner from the token itself, don't need to make a request to get it.
    }
    
    /**
     * @param AccessToken|null $token
     * @return array
     */
    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer ' . $token->getToken()
        ];
    }
}
