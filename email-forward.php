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
include "./include/email-parse.php";

$stdin = fopen( "php://stdin", "r" );

while( !feof( $stdin ) )
  $email .= fread( $stdin, 10240 );

fclose( $stdin );

return parse_email_to_ticket( $email, "" );

/********************************************************** PHP */?>
