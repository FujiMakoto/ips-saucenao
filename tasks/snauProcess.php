<?php
/**
 * @brief		snauProcess Task
 * @author		<a href='https://www.invisioncommunity.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) Invision Power Services, Inc.
 * @license		https://www.invisioncommunity.com/legal/standards/
 * @package		Invision Community
 * @subpackage	saucenao
 * @since		05 Feb 2020
 */

namespace IPS\saucenao\tasks;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\File;
use IPS\gallery\Image;
use IPS\saucenao\Exception\ApiLimitException;
use IPS\saucenao\Exception\FileSizeException;
use IPS\saucenao\Exception\SauceNaoException;
use IPS\saucenao\SauceNao\Sauce;
use IPS\saucenao\SauceNao\SauceNaoApi;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * snauProcess Task
 */
class _snauProcess extends \IPS\Task
{
	/**
	 * Execute
	 *
	 * If ran successfully, should return anything worth logging. Only log something
	 * worth mentioning (don't log "task ran successfully"). Return NULL (actual NULL, not '' or 0) to not log (which will be most cases).
	 * If an error occurs which means the task could not finish running, throw an \IPS\Task\Exception - do not log an error as a normal log.
	 * Tasks should execute within the time of a normal HTTP request.
	 *
	 * @return	mixed	Message to log or NULL
	 * @throws	\IPS\Task\Exception
	 */
	public function execute()
	{
	    // Check if we have reached our API limits and need to wait
	    $sleepUntil = NULL;
        try
        {
            $sleepUntil = \IPS\Data\Store::i()->snau_sleep;
        }
        catch ( \OutOfRangeException $e ) {}

        if ( $sleepUntil and ( \time() < $sleepUntil ) )
        {
            return 'API limit exceeded! Waiting..';
        }

        // Get some images to process
        $snauSelect = \IPS\Db::i()->select( 'item_id', Sauce::$databaseTable, [ 'app=? AND item_id IS NOT NULL', 'gallery' ] );
        $existingIds = \iterator_to_array( $snauSelect );
        $where = $existingIds ? [ \IPS\Db::i()->in( 'image_id', $existingIds, TRUE ) ] : NULL;

        $gallerySelect = \IPS\Db::i()->select( '*', Image::$databaseTable, $where, 'image_id DESC', 15 );
        foreach ( $gallerySelect as $image )
        {
            $image = Image::constructFromData( $image );
            try
            {
                $sauce = new SauceNaoApi;
                $sauce = $sauce->fromUrl( File::get( 'gallery_Images', $image->masked_file_name )->url );
            }
            catch ( ApiLimitException $e )
            {
                if ( \strpos( $e->getMessage(), 'searches every 30 seconds') )
                {
                    return $e->getMessage();
                }

                $sleepUntil = \time() + 10800;
                \IPS\Data\Store::i()->snau_sleep = $sleepUntil;
                return $e->getMessage();
            }
            catch ( FileSizeException $e )
            {
                return "Image {$image->image_id} too large to lookup, skipping";
            }
            catch ( SauceNaoException $e )
            {
                Sauce::createFromResponse( ['header' => ['results_returned' => 0]], $image );
//                return "An unknown error occurred looking up image {$image->id}";
                continue;
            }

            Sauce::createFromResponse( $sauce, $image );
        }
	}
	
	/**
	 * Cleanup
	 *
	 * If your task takes longer than 15 minutes to run, this method
	 * will be called before execute(). Use it to clean up anything which
	 * may not have been done
	 *
	 * @return	void
	 */
	public function cleanup()
	{
		
	}
}