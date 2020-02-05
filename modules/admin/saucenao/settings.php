<?php


namespace IPS\saucenao\modules\admin\saucenao;

/* To prevent PHP errors (extending class does not exist) revealing path */

use IPS\Helpers\Form;
use IPS\Member;
use IPS\Settings;

if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * settings
 */
class _settings extends \IPS\Dispatcher\Controller
{
	/**
	 * Execute
	 *
	 * @return	void
	 */
	public function execute()
	{
		\IPS\Dispatcher::i()->checkAcpPermission( 'settings_manage' );
		\IPS\Output::i()->title = Member::loggedIn()->language()->addToStack( 'menu__saucenao_saucenao_settings' );
		parent::execute();
	}

	/**
	 * ...
	 *
	 * @return	void
	 */
	protected function manage()
	{
		$form = new Form();

		$form->addHeader( 'snau_api_settings' );
		$form->add( new Form\Text( 'snau_api_key', Settings::i()->snau_api_key ) );
        $form->add(
            new Form\Number(
                'snau_min_similarity', Settings::i()->snau_min_similarity, TRUE,
                [ 'min' => 10, 'max' => 90, 'step' => 5, 'endSuffix' => '%' ]
            )
        );

        if ( $form->values() )
        {
            $form->saveAsSettings();
        }

		\IPS\Output::i()->output = (string) $form;
	}
	
	// Create new methods with the same name as the 'do' parameter which should execute it
}