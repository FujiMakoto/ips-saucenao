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
        \IPS\Output::i()->breadcrumb = [];
        \IPS\Output::i()->breadcrumb[] = [
            \IPS\Http\Url::internal( "app=gallery&module=gallery&controller=browse", 'front', 'gallery' ),
            \IPS\Member::loggedIn()->language()->addToStack( 'module__gallery_gallery' )
        ];
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

        $index_id  = (int) \IPS\Request::i()->index;
        $author_id = (int) \IPS\Request::i()->author;

        // Get all other gallery images with this sauce
        try
        {
            $sauce = Sauce::loadLatest( $index_id, $author_id );
        }
        catch ( \UnderflowException $e )
        {
            \IPS\Output::i()->error( 'node_error', '2SNA201/1', 404 );
            return;
        }

//        \IPS\Output::i()->breadcrumb[] = [ NULL, 'module__saucenao_sauce' ];
//        \IPS\Output::i()->breadcrumb[] = [ NULL, "snau_index_{$sauce->index_id}" ];
        \IPS\Output::i()->breadcrumb[] = [ NULL, $sauce->author_name ];

        $sauceIds = $sauce->galleryImages();
        if ( !$sauceIds )
        {
            \IPS\Output::i()->error( 'node_error', '2SNA201/2', 404 );
            return;
        }

        // Still here? Great! Let's build the table query
        $table = new \IPS\gallery\Image\Table(
            'IPS\gallery\Image', $sauce->url(), [ \IPS\Db::i()->in( 'image_id', $sauceIds ) ]
        );
        $table->limit = 50;
        $table->tableTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'gallery' ), 'imageTable' );
        $table->rowsTemplate = array( \IPS\Theme::i()->getTemplate( 'browse', 'gallery' ), $this->getTableRowsTemplate() );

        \IPS\Output::i()->output = \IPS\Theme::i()->getTemplate( 'gallery' )->browse( $sauce, (string) $table );
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