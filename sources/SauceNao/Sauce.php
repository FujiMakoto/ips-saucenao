<?php

namespace IPS\saucenao\SauceNao;

/* To prevent PHP errors (extending class does not exist) revealing path */

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
}