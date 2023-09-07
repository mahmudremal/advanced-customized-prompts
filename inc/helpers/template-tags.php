<?php
/**
 * Custom template tags for the theme.
 *
 * @package SOSPopsProject
 */
if( ! function_exists( 'is_FwpActive' ) ) {
  function is_FwpActive( $opt ) {
    if( ! defined( 'SOSPOPSPROJECT_OPTIONS' ) ) {return false;}
    return ( isset( SOSPOPSPROJECT_OPTIONS[ $opt ] ) && SOSPOPSPROJECT_OPTIONS[ $opt ] == 'on' );
  }
}
if( ! function_exists( 'get_FwpOption' ) ) {
  function get_FwpOption( $opt, $def = false ) {
    if( ! defined( 'SOSPOPSPROJECT_OPTIONS' ) ) {return false;}
    return isset( SOSPOPSPROJECT_OPTIONS[ $opt ] ) ? SOSPOPSPROJECT_OPTIONS[ $opt ] : $def;
  }
}