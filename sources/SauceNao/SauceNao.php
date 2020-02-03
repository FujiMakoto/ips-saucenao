<?php

namespace IPS\saucenao;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Http\Request\Curl;
use IPS\Http\Url;
use IPS\Patterns\Singleton;
use IPS\saucenao\Exception\ApiKeyException;
use IPS\saucenao\Exception\ApiLimitException;
use IPS\saucenao\Exception\FileSizeException;
use IPS\saucenao\Exception\SauceNaoException;
use IPS\Settings;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * SauceNao API library
 * @package	IPS\saucenao
 */
class _SauceNao extends Singleton
{
    const ENDPOINT = 'https://saucenao.com/search.php';

    /**
     * @var Url
     */
    protected $endpoint;

    /**
     * Required similarity percent to be considered a match
     * Default is 75%, but can be configured
     * @var string
     */
    protected $reqSimilarity;

    /**
     * _SauceNao constructor
     */
    public function __construct()
    {
        $this->endpoint = Url::external( static::ENDPOINT );
        $this->endpoint = $this->endpoint->setQueryString([
            'api_key'       => Settings::i()->snau_api_key,
            'output_type'   => '2',
            'db'            => '999',
            'numres'        => '1',
            'minsim'        => Settings::i()->snau_min_similarity
        ]);
    }

    /**
     * Get the source of an image from its URL
     * @param string $url
     * @return array
     * @throws ApiKeyException
     * @throws ApiLimitException
     * @throws FileSizeException
     * @throws SauceNaoException
     */
    public function fromUrl( string $url )
    {
        /** @var Curl $request */
        $request = $this->endpoint->setQueryString( 'url', $url )->request();

        $response = $request->get();
        $this->checkStatusCode( $response->httpResponseCode );
        $this->checkResponse( $response->decodeJson() );

        return $response->decodeJson();
    }

    /**
     * Check the HTTP status code and throw an exception if an error occurred
     * @param int $statusCode
     * @throws ApiKeyException
     * @throws ApiLimitException
     * @throws FileSizeException
     * @throws SauceNaoException
     */
    public function checkStatusCode( int $statusCode )
    {
        // Bad API key
        if ( $statusCode === 403 )
        {
            //Output::i()->error( 'snau_error_badApiKey', '4SNA101/1', 403 );
            throw new ApiKeyException();
        }
        // API limit exceeded
        elseif ( $statusCode === 429 )
        {
            throw new ApiLimitException();
        }
        // File too large
        elseif ( $statusCode === 413 )
        {
            throw new FileSizeException();
        }
        // Unknown error
        elseif ( $statusCode !== 200 )
        {
            throw new SauceNaoException( 'node_error', $statusCode );
        }
    }

    /**
     * Make sure we received a valid response
     * @param $response
     * @throws SauceNaoException
     */
    public function checkResponse( $response )
    {
        if ( !$response or ( $response['header']['status'] < 0 ) )
        {
            throw new SauceNaoException( $response['header']['message'], $response['header']['status'] );
        }
    }
}