//<?php

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !\defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	exit;
}

class saucenao_hook_T_GalleryFrontView extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'imageInfo' => 
  array (
    0 => 
    array (
      'selector' => '#elGalleryImageStats > div.ipsBox.ipsPad[data-role=\'imageStats\']',
      'type' => 'add_after',
      'content' => '<div id="snauAuthorStats" class="ipsBox ipsPad">
  	<div class="ipsType_center"></div>
	<hr class="ipsHr">		
</div>',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
