<?php

namespace IPS\saucenao\SauceNao;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gallery\Image;
use IPS\Member;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
    header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
    exit;
}

/**
 * SauceNao API response container
 * @property int $id ID Number
 * @property int|NULL $index_id Index ID. Actual index name is stored as a language string.
 * @property int|NULL $similarity Rounded similarity percentage (0-100)
 * @property string|NULL $author_name Authors display name
 * @property string|NULL $author_url URL to the author's profile page
 * @property int|NULL $author_id Remote member ID of the author.
 * @property string|NULL $title Original title of the image
 * @property string|NULL $url URL to the original source of the image
 * @property string $app Application name
 * @property int $item_id Item ID
 * @property int|NULL $illust_id Remote database ID of the original source image
 * @package	IPS\saucenao
 */
class _Sauce extends \IPS\Patterns\ActiveRecord
{
    /**
     * @var string
     */
    public static $databaseColumnId = 'id';

    /**
     * @var string
     */
    public static $databaseTable = 'saucenao_sauce';

    /**
     * @var string
     */
    public static $databasePrefix = '';

    /**
     * Cached gallery ID's
     * @var array|null
     */
    protected $_galleryIds = NULL;

    /**
     * @var int|null
     */
    protected $_count = NULL;

    /**
     * Return the sauce of the item in question, if available
     * @param \IPS\Content\Item $item
     * @return _Sauce|null
     */
    public static function loadItemSauce( \IPS\Content\Item $item )
    {
        $app = $item::$application;
        $id  = $item->id;

        try
        {
            $s = \IPS\Db::i()->select( '*', static::$databaseTable, ['app=? AND item_id=?', $app, $id] )->first();
        }
        catch ( \UnderflowException $e )
        {
            return NULL;
        }

        return static::constructFromData( $s );
    }

    /**
     * Save the source of an item
     * @param array             $response
     * @param \IPS\Content\Item $item
     * @return static
     */
    public static function createFromResponse( array $response, \IPS\Content\Item $item )
    {
        // Delete any existing entries
        $existing = static::loadItemSauce( $item );
        if ( $existing )
        {
            $existing->delete();
        }

        $sauce = new static;

        // Save the item link
        $sauce->app = $item::$application;
        $sauce->item_id = $item->id;

        // No results? Save this as an empty result so we don't query it again.
        if ( !$response['header']['results_returned'] )
        {
            $sauce->save();
            return $sauce;
        }

        // Otherwise, get the first result and save it
        $header = $response['results'][0]['header'];
        $data   = $response['results'][0]['data'];

        $sauce->similarity  = \round( $header['similarity'] );
        $sauce->index_id    = $header['index_id'];

        // Original title
        if ( isset( $data['title'] ) )
        {
            $sauce->title = $data['title'];
        }
        elseif ( isset( $data['eng_name'] ) )
        {
            $sauce->title = $data['eng_name'];
        }
        elseif ( isset( $data['material'] ) )
        {
            $sauce->title = $data['material'];
        }
        elseif ( isset( $data['source'] ) )
        {
            $sauce->title = $data['source'];
        }

        // Illustration ID
        $illust_id = NULL;
        switch ( $sauce->index_id )
        {
            case 5:
            case 6:
                $illust_id = $data['pixiv_id'];
                break;
            case 8:
                $illust_id = $data['seiga_id'];
                break;
            case 10:
                $illust_id = $data['drawr_id'];
                break;
            case 11:
                $illust_id = $data['nijie_id'];
                break;
            case 34:
                $illust_id = $data['da_id'];
                break;
        }

        if ( $illust_id )
        {
            $sauce->illust_id = $illust_id;
        }

        // Author name
        if ( isset( $data['member_name'] ) )
        {
            $sauce->author_name = $data['member_name'];
        }
        elseif ( isset( $data['creator'] ) )
        {
            $sauce->author_name = \is_array( $data['creator'] ) ? $data['creator'][0] : $data['creator'];
        }

        // Author ID
        if ( isset( $data['member_id'] ) )
        {
            $sauce->author_id = $data['member_id'];
        }

        // Author URL
        if ( isset( $data['author_url'] ) )
        {
            $sauce->author_url = $data['author_url'];
        }
        elseif ( isset( $data['pawoo_id'] ) and isset( $data['ext_urls'] ) )
        {
            $sauce->author_url = $data['ext_urls'][0];
        }
        elseif ( $header['index_id'] == 5 )  // Pixiv source
        {
            $sauce->author_url = "https://www.pixiv.net/member.php?id={$data['member_id']}";
        }
        elseif ( isset( $data['source'] ) )
        {
            $sauce->author_url = $data['source'];
        }

        // Regular URL
        if ( isset( $data['ext_urls'] ) )
        {
            $sauce->url = $data['ext_urls'][0];
        }

        // All done!
        $sauce->save();
        return $sauce;
    }

    /**
     * Get all gallery images (or just their ID's) associated with this artist
     * @param bool $idsOnly
     * @return array[int]|array[Image]|array
     */
    public function galleryImages( bool $idsOnly = TRUE )
    {
        // If we don't have an author ID, we can't look up anything. Duh.
        if ( !$this->author_id )
        {
            return [];
        }

        // Check for cached data
        if ( ( $this->_galleryIds !== NULL ) and $idsOnly )
        {
            return $this->_galleryIds;
        }

        $s = \IPS\Db::i()->select(
            'item_id', static::$databaseTable,
            [ 'app=? AND index_id=? AND author_id=?', 'gallery', $this->index_id, $this->author_id ]
        );

        $this->_galleryIds = \iterator_to_array( $s );
        if ( $idsOnly )
        {
            return $this->_galleryIds;
        }

        // We don't really use this, but keep it as an option for others
        $s = \IPS\Db::i()->select(
            '*', Image::$databaseTable, [ \IPS\Db::i()->in( Image::$databaseColumnId, $this->_galleryIds ) ]
        );

        // I miss Python generators.
        $images = [];
        foreach ( $s as $item )
        {
            $images[] = Image::constructFromData( $item );
        }
        return $images;
    }

    /**
     * Get the number of images this artist has in the gallery
     * @return int
     */
    public function count()
    {
        return ( $this->_count !== NULL ) ? $this->_count : $this->_count = \count( $this->galleryImages() );
    }

    /**
     * Get the index title
     * @param bool $includeIcon
     * @return string
     */
    public function indexTitle( $includeIcon = TRUE )
    {
        $output = '';

        // Do we have an icon we want to display?
        $icon = NULL;
        if ( $includeIcon )
        {
            switch ( $this->index_id )
            {
                case 5:
                    $icon = \IPS\Theme::i()->resource( 'pixiv.png', 'saucenao', 'front' );
                    break;
                case 9:
                case 25:
                    $icon = \IPS\Theme::i()->resource( 'booru.png', 'saucenao', 'front' );
                    break;
                case 8:
                    $icon = \IPS\Theme::i()->resource( 'nico.png', 'saucenao', 'front' );
                    break;
                case 34:
                    $icon = \IPS\Theme::i()->resource( 'da.png', 'saucenao', 'front' );
                    break;
            }
        }

        if ( $icon )
        {
            $output = "<img src='{$icon}' class='snauSauceIcon'> &nbsp;";
        }

        return $output . Member::loggedIn()->language()->addToStack( "snau_index_{$this->index_id}" );
    }

    /**
     * Get a link to the author's page if available, or just the authors name if not, or nothing if neither exist
     * @return string|null
     */
    public function authorLink()
    {
        if ( !$this->author_name )
        {
            return NULL;
        }

        if ( $this->author_url )
        {
            return "<a href='{$this->author_url}' target='_blank' rel='noopener'>{$this->author_name}</a>";
        }

        // No author URL? Just return the name then
        return $this->author_name;
    }

    /**
     * Make sure we have a valid URL before setting it
     * @param $val
     */
    protected function set_author_url( $val )
    {
        if ( ( \substr( $val, 0, 7 ) !== 'http://' ) and ( \substr( $val, 0, 8 ) !== 'https://' ) )
        {
            $this->_data['author_url'] = NULL;
            return;
        }

        $this->_data['author_url'] = $val;
    }

    /**
     * @brief	Cached URLs
     */
    protected $_url	= [];

    /**
     * @brief	URL Base
     */
    public static $urlBase = 'app=saucenao&module=sauce&controller=gallery&index={index_id}&author={author_id}';

    /**
     * @brief	URL Base
     */
    public static $urlTemplate = 'snau_gallery';

    /**
     * @brief	SEO Title Column
     */
    public static $seoTitleColumn = ['index' => 'seo_index', 'author' => 'seo_author'];

    /**
     * Get URL
     *
     * @param	string|NULL		$action		Action
     * @return	\IPS\Http\Url
     * @throws	\BadMethodCallException
     * @throws	\IPS\Http\Url\Exception
     */
    public function url( $action=NULL )
    {
        $_key	= md5( $action );

        if( !isset( $this->_url[ $_key ] ) )
        {
            $seoTitleColumn = static::$seoTitleColumn;

            try
            {
                $urlBase = \str_replace( '{index_id}', $this->index_id, static::$urlBase );
                $urlBase = \str_replace( '{author_id}', $this->author_id, $urlBase );
                $url = \IPS\Http\Url::internal(
                    $urlBase, 'front', static::$urlTemplate,
                    [ $this->seo_index, $this->seo_author ]
                );
            }
            catch ( \IPS\Http\Url\Exception $e )
            {
                $indexTitle = \IPS\Lang::load( \IPS\Lang::defaultLanguage() )->get( "snau_index_{$this->index_id}" );
                $indexSeo   = \IPS\Http\Url\Friendly::seoTitle( $indexTitle );
                $authorSeo  = \IPS\Http\Url\Friendly::seoTitle( $this->author_name );

                $this->seo_index  = $indexSeo;
                $this->seo_author = $authorSeo;
                $this->save();

                return $this->url( $action );
            }

            $this->_url[ $_key ] = $url;

            if ( $action )
            {
                $this->_url[ $_key ] = $this->_url[ $_key ]->setQueryString( 'do', $action );
            }
        }

        return $this->_url[ $_key ];
    }
}