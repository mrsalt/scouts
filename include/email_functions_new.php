<?php
function send_email($from, $to, $subject, $message, $wrap = 70)
{
	if ($wrap)
		$message = wordwrap($message, $wrap);
	$from = 'From: ' . $from;
	$rvalue = mail( $to, $subject, $message, $from);
	return $rvalue;
}
?>