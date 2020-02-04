<?php


namespace IPS\saucenao\modules\front\sauce;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\gallery\Image;
use IPS\saucenao\SauceNao\Sauce;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * gallery
 */
class _gallery extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
        \IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js('front_browse.js', 'gallery' ) );
		parent::execute();
	}

	/**
	 * Display images matching the same sauce as the specified gallery image
	 *
	 * @return	void
	 */
	protected function manage()
	{
        \IPS\Output::i()->title = 'snau_gallery_title';
        $url = \IPS\Http\Url::internal( 'app=saucenao&module=sauce&controller=gallery' )->setQueryString( 'image_id', \IPS\Request::i()->image_id );

        // Load the gallery image
        try
        {
            $image = Image::loadAndCheckPerms( \IPS\Request::i()->image_id );
        }
        catch ( \OutOfRangeException $e )
        {
            \IPS\Output::i()->error( 'node_error', '2SNA201/1', 404 );
            return;
        }

        // Load the images sauce entry
        $sauce = Sauce::loadItemSauce( $image );
        if ( !$sauce )
        {
            \IPS\Output::i()->error( 'node_error', '2SNA201/2', 404 );
        }

        // Get all other gallery images with this sauce
        $sauceIds = $sauce->galleryImages();
        if ( !$sauceIds )
        {
            \IPS\Output::i()->error( 'node_error', '2SNA201/3', 404 );
        }

        // Still here? Great! Let's build the table query
        $table = new \IPS\gallery\Image\Table(
            'IPS\gallery\Image', $url, [ \IPS\Db::i()->in( 'image_id', $sauceIds ) ]
        );
        $table->limit = 50;
        $table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'gallery' ), 'imageTable' );
        $table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'gallery' ), $this->getTableRowsTemplate() );

        \IPS\Output::i()->output = (string) $table;
	}

    /**
     * Determine which table rows template to use
     *
     * @return	string
     */
    protected function getTableRowsTemplate()
    {
        if( isset( \IPS\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Request::i()->cookie['thumbnailSize'] == 'large' AND \IPS\Request::i()->controller != 'search' )
        {
            return 'tableRowsLarge';
        }
        else if( isset( \IPS\Request::i()->cookie['thumbnailSize'] ) AND \IPS\Request::i()->cookie['thumbnailSize'] == 'rows' AND \IPS\Request::i()->controller != 'search' )
        {
            return 'tableRowsRows';
        }
        else
        {
            return 'tableRowsThumbs';
        }
    }
}