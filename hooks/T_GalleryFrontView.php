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
      'content' => '{{if $sauce = \IPS\saucenao\SauceNao\Sauce::loadItemSauce($image)}}
  <div id="snauAuthorStats" class="ipsBox ipsPad">
      <div class="ipsType_center"><h2 class="ipsType_minorHeading">{$sauce->indexTitle()|raw}</h2></div>
      <hr class="ipsHr">
      {{if $sauce->authorLink()}}
        <h2 class="ipsType_minorHeading">{lang="snau_artist"}</h2>
        <p class="ipsType_reset ipsType_normal ipsType_blendLinks ipsType_light snau_authorLink">{$sauce->authorLink()|raw}</p>
      {{endif}}
  </div>
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
