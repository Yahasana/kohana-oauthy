<?php

class Oauth_Parameter_Assertion extends Oauth_Parameter {

    /**
     * assertion_format
     *      REQUIRED.  The format of the assertion as defined by the
     *      authorization server.  The value MUST be an absolute URI.
     */
    public $assertion_format;

    /**
     * assertion
     *      REQUIRED.  The assertion.
     */
    public $assertion;

    /**
     * client_id
     *      OPTIONAL.  The client identifier as described in Section 2.1.
     *      The authorization server MAY require including the client
     *      credentials with the request based on the assertion properties.
     * client_secret
     *      OPTIONAL.  The client secret as described in Section 2.1.  MUST
     *      NOT be included if the "client_id" parameter is omitted.
     * scope
     *      OPTIONAL.  The scope of the access request expressed as a list
     *      of space-delimited strings.  The value of the "scope" parameter
     *      is defined by the authorization server.  If the value contains
     *      multiple space-delimited strings, their order does not matter,
     *      and each string adds an additional access range to the
     *      requested scope.
     * format
     *      OPTIONAL.  The response format requested by the client.  Value
     *      MUST be one of "json", "xml", or "form".  Alternatively, the
     *      client MAY use the HTTP "Accept" header field with the desired
     *      media type.  Defaults to "json" if omitted and no "Accept"
     *      header field is present.
     */

    public function __construct($flag = FALSE)
    {
        $this->assertion_format = Oauth::get('username');
        $this->assertion = Oauth::get('password');
        $this->client_id = Oauth::get('client_id');
        $this->client_secret = Oauth::get('client_secret');
        $this->scope = Oauth::get('scope');
        $this->format = Oauth::get('format');
    }

    public function oauth_token($client)
    {
        $response = new Oauth_Response;

        if(! empty($this->assertion_format) AND $client['format'] !== $this->assertion_format)
        {
            $response->error = 'unknown_format';
            return $response;
        }
        else
        {
            $response->assertion_format = $this->assertion_format;
        }

        if($client['assertion'] !== $this->assertion
            OR (! empty($this->client_id) AND $client['client_id'] !== $this->client_id)
            OR (! empty($this->client_secret) AND $client['client_secret'] !== sha1($this->client_secret))
            OR (! empty($client['scope']) AND ! isset($client['scope'][$this->scope]))
        {
            $response->error = 'invalid_assertion';
            return $response;
        }

        // Grants Authorization
        // The authorization server SHOULD NOT issue a refresh token.
        $response->expires_in = 3000;
        $response->access_token = $client['access_token'];

        return $response;
    }

    public function access_token($client)
    {
        return new Oauth_Token;
    }
}
