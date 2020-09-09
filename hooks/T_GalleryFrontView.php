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
      'selector' => '#elGalleryImageStats > div[data-role=\'imageStats\']',
      'type' => 'add_after',
      'content' => '{{if $sauce = \IPS\saucenao\SauceNao\Sauce::loadItemSauce($image)}}
  <div id="snauAuthorStats" class="ipsBox ipsPad">
      <div class="ipsType_center"><h2 class="ipsType_minorHeading">{$sauce->indexTitle()|raw}</h2></div>
      <hr class="ipsHr">
      {{if $sauce->authorLink()}}
        <h2 class="ipsType_minorHeading">{lang="snau_artist"}</h2>
        <p class="ipsType_reset ipsType_normal ipsType_blendLinks ipsType_light snau_authorLink">{$sauce->authorLink()|raw}</p>
      {{endif}}
      {{if $sauce->illust_id}}
        <h2 class="ipsType_minorHeading" {{if $sauce->authorLink()}}style="padding-top: 15px;"{{endif}}>{lang="snau_illust_id"}</h2>
        <p class="ipsType_reset ipsType_normal ipsType_blendLinks ipsType_light snau_authorLink">{$sauce->illust_id}</p>
      {{endif}}
      {{if $sauce->count() > 1}}
        <div class="ipsType_center ipsPad">
          <a href="{$sauce->url()}" class="ipsButton ipsButton_light ipsButton_verySmall ipsButton_fullWidth">{lang="snau_view" pluralize="$sauce->count() - 1"}</a>
        </div>
      {{endif}}
  </div>
{{endif}}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */


}
