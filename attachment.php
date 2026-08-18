<?php 
////////////////////////////////////////////////////////////////////
// LynxHD Formely ColdBrew Help Desk  
// -----------------------------------------------------------------
//
// License info can be found in license.txt.
// You must leave this notice as is.
//
// LynxHD Formely ColdBrew Helpdesk has been modified and mantained by:
//
//      Old Author: James Paige
//      New Author: Trilex Labs
//         Web: http://www.lynxhd.com
// -----------------------------------------------------------------
////////////////////////////////////////////////////////////////////
include "./include/settings.php";
include "./include/include.php";

$_GET[file] = str_replace( "..", "", $_GET[file] );

$res = mysql_query( "SELECT id FROM {$pre}ticket WHERE ( ticket_id = '{$_GET[id]}' && email = '{$_GET[email]}' )" );
$row = mysql_fetch_array( $res );
if( $row )
{
  Header( "Content-type: application/octet-stream" );
  Header( "Content-disposition: inline; filename={$_GET[file]}" ); 

  $fp = @fopen( "{$HD_TICKET_FILES}/{$row[id]}/{$_GET[file]}", "r" );
  if( $fp )
  {
    while( !feof( $fp ) )
      echo fread( $fp, 10240 );

    fclose( $fp );
  }
}

/********************************************************** PHP */?>