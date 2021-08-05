<?php


namespace Yoco;


use Yoco\Exceptions\DeclinedException;
use Yoco\Exceptions\InternalException;
use Yoco\Exceptions\ApiKeyException;

class YocoClient
{

    /** @var string API base URL */
    private const BASE_URL = 'https://online.yoco.com';

    // strlen("sk_test_") etc.
    private const KEY_PREFIX_LEN = 8;

    private $secret_key;
    private $public_key;
    
    public function __construct($secret_key, $public_key)
    {
        $this->secret_key = $secret_key;
        $this->public_key = $public_key;
    }
    
    /**
     * Returns the public key
     *
     * @return string the public key
     */
    public function getPublicKey()
    {
        return $this->public_key;
    }

    /**
     * Returns the redacted secret key
     *
     * @return string redacted secret key
     */
    public function getRedactedSecretKey()
    {
        // keep prefix, replace rest with asterisks
        $asterisks = strlen($this->secret_key) - self::KEY_PREFIX_LEN;
        return substr($this->secret_key, 0, self::KEY_PREFIX_LEN).str_repeat("*", $asterisks);
    }

    /**
     * Internal method to POST a request to the Yoco Api
     *
     * @param string $path string The path of the resource to which to POST.
     * @param array $data An object containing the post body.
     * @param bool $retry Retry on `4XX` failures.
     * @return string result
     * @throws ApiKeyException If there is an error with the Api Keys    
     */
    private function post($path, $data, $retry)
    {
        $this->validateKeys();

        // Initialise the curl handle
        $ch = curl_init();

        // Setup curl
        curl_setopt($ch, CURLOPT_URL, self::BASE_URL.$path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secret_key);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // send to yoco
        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // close the connection
        curl_close($ch);

        if ($http_code < 300) {
            return $result;
        } elseif ($http_code < 500) {
            if ($retry) {
                return $this->post($path, $data, false);
            }
            throw new DeclinedException(json_decode($result));
        } else {
            throw new InternalException(json_decode($result));
        }
    }

    /**
     * Method for server-to-server charge completion
     *
     * @param string $token The chargeToken, attained from the Yoco Web SDK and passed to server, e.g. `tok_DgfdsVFvfbvwjkhdbxv`.
     * @param int $amountInCents A positive integer representing the amount in cents.
     * @param string $currency An ISO 4217 currency code. Must be `ZAR`.
     * @param array $metadata Collection of key value pairs which will be passed back in the result.
     * @param bool $retry Retry on `4XX` failures.
     * @return ChargeResult
     * @throws DeclinedException If there is a card/data error
     * @throws InternalException If there is a system error
     * @throws ApiKeyException If there is an error with the Api Keys
     */
    public function charge($token, $amountInCents, $currency, $metadata = [], $retry = false)
    {
        $data = [
            'token' => $token,
            'amountInCents' => $amountInCents,
            'currency' => $currency,
            'metadata' => json_encode($metadata),
        ];

        $result = $this->post("/v1/charges/", $data, $retry);
        return new ChargeResult(json_decode($result));
    }    

    /**
     * @param string $chargeId The `id` as returned from the charge call. See `ChargeResult`.
     * @param array $metadata Collection of key value pairs which will be passed back in the result.
     * @param bool $retry Retry on `4XX` failures.
     * @return RefundResult
     * @throws DeclinedException If there is a card/data error
     * @throws InternalException If there is a system error
     * @throws ApiKeyException If there is an error with the Api Keys
     */
    public function refund($chargeId, $metadata = [], $retry = false)
    {
        $data = [
            'chargeId' => $chargeId,
            'metadata' => $metadata,
        ];

        $result = $this->post("/v1/refunds/", $data, $retry);
        return new RefundResult(json_decode($result));
    }    

    /**
     * Returns environment targeted by the keys ("test" / "live" / "mixed")
     *
     * @return string
     */
    public function keyEnvironment()
    {
        if ( preg_match('/_live_/', $this->secret_key) && preg_match('/_live_/', $this->public_key))
            return "live";
        else if ( preg_match('/_test_/', $this->secret_key) && preg_match('/_test_/', $this->public_key))
            return "test";
        else 
            return "mixed";
    }

    /**
     * Validate the API keys are in the correct format and have matching target environments (live or test).
     *
     * @throws ApiKeyException If there is an error with the Api Keys
     */    
    private function validateKeys()
    {
        if (!$this->keysLookCorrect()) {

            $errorMessage = implode("\n", $this->getKeyErrors());
            $error = [
                'errorType' => 'invalid_request_error',
                'errorCode' => 'wrong_api_key',
                'errorMessage' => 'The provided Api Keys are not valid.'.$errorMessage,
                'displayMessage' => 'There is a configuration error. Please contact support.'
            ];
            $errorObj = (object) $error;

            throw new ApiKeyException($errorObj);
        }
    }

    /**
     * Simple check to confirm API keys look valid
     *
     * @return bool
     */
    public function keysLookCorrect()
    {
        return (
                preg_match('/^sk_test_/', $this->secret_key)
                && preg_match('/^pk_test_/', $this->public_key)
            )
            || (
                preg_match('/^sk_live_/', $this->secret_key)
                && preg_match('/^pk_live_/', $this->public_key)
            );
    }

    /**
     * Returns any formatting errors found in the API keys
     *
     * @return array
     */    
    public function getKeyErrors()
    {
        $errors = [];
        if ( !preg_match('/^sk_test_/', $this->secret_key) && !preg_match('/^sk_live_/', $this->secret_key) ) {
            $errors[] = 'Secret key prefix is incorrect.';
        }
        if ( !preg_match('/^pk_test_/', $this->public_key) && !preg_match('/^pk_live_/', $this->public_key) ) {
            $errors[] = 'Public key prefix is incorrect.';
        }

        if ( strlen($this->secret_key) != (8+28) ) {
            $errors[] = 'Secret key length is incorrect.';
        }
        if ( strlen($this->public_key) != (8+20) ) {
            $errors[] = 'Public key length is incorrect.';
        }

        $key_mismatch = (
            preg_match('/^sk_test/', $this->secret_key)
            && preg_match('/^pk_live/', $this->public_key)
        )
        || (
            preg_match('/^sk_live/', $this->secret_key)
            && preg_match('/^pk_test/', $this->public_key)
        );            
        if ( $key_mismatch ) {
            $errors[] = 'Mixing test and live keys.';
        }

        return $errors;
    }
}
