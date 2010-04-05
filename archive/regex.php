<?php
function pre_print_r($text)
{
	echo '<pre>';
	print_r($text);
	echo '</pre>';
}

echo '<html>';
echo '<head><title>Reg Ex Test</title></head>';
echo '<body>';

if (array_key_exists('regex',$_POST)){
	if (preg_match(stripslashes($_POST['regex']), stripslashes($_POST['subject']), $match)){
		echo 'Match Found: <br/>';
		$i = 0;
		foreach ($match as $part)
		{
			pre_print_r('$match['.($i++).'] = ');
			pre_print_r(htmlentities($part));
		}
	}
	else {
		echo 'Match Not Found.<br/>';
	}
	echo '<br/><br/>';
}
?>

<form action="/regex.php" method="POST">
<table>
<tr>
<td>PCRE RegEx:</td><td>
<?php
echo '<input type="text" style="width: 300px;" name="regex" value="'.(array_key_exists('regex',$_POST) ? stripslashes($_POST['regex']) : '' ).'">';
?>
</td></tr>
<tr><td>Subject:</td><td>
<textarea name="subject" rows=10 cols=100>
<?php
if (array_key_exists('subject',$_POST))
	echo stripslashes($_POST['subject']);
?>
</textarea>
</td></tr></table>
<input type="submit" value="Submit"/>
</form>
</body>
</html>