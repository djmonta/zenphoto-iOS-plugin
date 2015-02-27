<?php
/**
 * Update ZenRPC.php
 */
function updateRPC( $gitbranch )
{
	$updatebase = "https://raw.github.com/djmonta/zenphoto-iOS-plugin/".$gitbranch."/iOS/ZenRPC.php";
	$self       = "./iOS/ZenRPC.php";
	$contents   = file_get_contents( $updatebase );
	
	//$fp = @fopen( $self, 'w' );
  if ($fp = fopen($self, 'w')) {
    //$info = $content; 
		if (fwrite($fp, $contents)) {
	      return true;
	  }
    fclose($fp);
  }
  return false;
}

$task = file_get_contents('php://input');
$taskbits = explode( "=", $task );
//echo getOption('iphone_update');
if (!empty ($taskbits[0]))
$taskbits[0]( $taskbits[1].$c );

?>