<?php

namespace IPS\saucenao\SauceNao;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

use IPS\Http\Request\Curl;
use IPS\Http\Url;
use IPS\saucenao\Exception\ApiKeyException;
use IPS\saucenao\Exception\ApiLimitException;
use IPS\saucenao\Exception\FileSizeException;
use IPS\saucenao\Exception\SauceNaoException;
use IPS\Settings;

/**
 * SauceNao API library
 * @package	IPS\saucenao
 */
class _SauceNaoApi extends \IPS\Patterns\Singleton
{
    const ENDPOINT = 'https://saucenao.com/search.php';

    /**
     * @brief	Singleton Instances
     * @note	This needs to be declared in any child classes as well, only declaring here for editor code-complete/error-check functionality
     */
    protected static $instance = NULL;

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
        $request = $this->endpoint->setQueryString( 'url', $url )->request( \IPS\LONG_REQUEST_TIMEOUT );

        $response = $request->get();
        $this->checkStatusCode( $response->httpResponseCode, $response->decodeJson() );
        $this->checkResponse( $response->decodeJson() );

        return $response->decodeJson();
    }

    /**
     * Check the HTTP status code and throw an exception if an error occurred
     *
     * @param int   $statusCode
     * @param array $response
     * @throws ApiKeyException
     * @throws ApiLimitException
     * @throws FileSizeException
     * @throws SauceNaoException
     */
    public function checkStatusCode( int $statusCode, array $response )
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
            throw new ApiLimitException( $response['header']['message'] );
        }
        // File too large
        elseif ( $statusCode === 413 )
        {
            throw new FileSizeException();
        }
        // Unknown error
        elseif ( $statusCode !== 200 )
        {
            throw new SauceNaoException( $response, $statusCode );
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