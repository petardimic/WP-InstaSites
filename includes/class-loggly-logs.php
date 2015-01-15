<?php

/**
 * Class Loggly_logs
 * 
 * Allow to use the (free) Loggly API to log Kigo sites plugin errors.
 * This can be useful if you have unexpected errors.
 * 
 * * TO USE LOGGLY
 * Please create a Loggly account ( https://www.loggly.com/ )
 * Once logged go to: https://<your_domaine>.loggly.com/tokens
 * Copy you "Customer Token" and add the folowing line in your wp-config.php file:
 * define( 'LOGGLY_API_KEY', '<your_Customer_Token>' );
 * 
 */
class Loggly_logs {
	
	// This values can be modified
	const KIGO_WP_LIVE_TAG		= 'live';
	const KIGO_WP_DEV_TAG		= 'dev';
	const KIGO_WP_PLUGIN_TAG	= 'WPP';
	
	// DO not modify the following
	const LOGLLY_HTTP_TAG		= 'http';
	const LOGGLY_ENDPOINT		= 'https://logs-01.loggly.com/inputs/';
	

	private static $_url;
	private static $_default_tags;
	
	static public function log( $logs, $extraTags = array() )
	{
		if(
			!defined( 'LOGGLY_API_KEY' ) ||
			!is_array( $logs ) ||
			!is_array( $extraTags )
		) {
			return self::error_log( $logs );
		}
		
		// Create a "cached" URL and array of tags
		if(
			!is_string( self::$_url ) ||
			!is_array( self::$_default_tags )
		) {
			self::$_url = self::LOGGLY_ENDPOINT . LOGGLY_API_KEY . '/tag/';
			self::$_default_tags = array(
				self::LOGLLY_HTTP_TAG,
				kigo_is_live() ? self::KIGO_WP_LIVE_TAG : self::KIGO_WP_DEV_TAG,
				$_SERVER['SERVER_NAME'],
				self::KIGO_WP_PLUGIN_TAG
			);
		}
		
		$context = stream_context_create( array(
			'http' => array(
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query( $logs )
			)
		) );
		
		if(
			!is_string( $reply = @file_get_contents( self::$_url . implode( ',', array_merge( self::$_default_tags, $extraTags ) ), null, $context ) ) ||
			
			!is_array( $http_response_header ) || // See http://php.net/manual/en/reserved.variables.httpresponseheader.php
			!isset( $http_response_header[0] ) ||
			false === strpos( $http_response_header[0], '200' ) ||
			
			!is_array( $response = json_decode( $reply, true ) ) ||
			!isset( $response[ 'response' ] ) ||
			'ok' !== $response[ 'response' ]
		) {
			return self::error_log( $logs, true );
		}
		
		return self::error_log( $logs );
	}
	
	static private function error_log( $logs, $loggly_failled = false )
	{
		return error_log( ( $loggly_failled ? 'Loggly failed ' : '' ) . json_encode( $logs ) );
	}
}