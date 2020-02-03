//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class saucenao_hook_C_GalleryFrontView extends _HOOK_CLASS_
{
	/**
	 * Init
	 *
	 * @return	void
	 */
	public function execute()
	{
        \IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'gallery.css', 'saucenao', 'front' ) );
		return parent::execute();
	}

}
