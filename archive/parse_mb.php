<?php
require_once 'include/globals.php';
require_once 'include/scout_requirements_include.php';

for ($mb_number = 1; $mb_number < 150; $mb_number++)
{
	$_SESSION['TEST'] = false;
	$sql = 'select id from award where mb_number = '.$mb_number;
	if(!do_query($sql,'scouts'))
	{
		add_mb($mb_number);
	}
	if($_SESSION['TEST'] == true)
	{
		exit;
	}
}

function add_mb($mb_number)
{
//	$page = http_get('www.meritbadge.com','/mb/'.sprintf('%03d',$mb_number).'.htm');
	$page = http_get('reederhome.net','/scouts/mb/'.sprintf('%03d',$mb_number).'.htm');

//	$filename = 'mb/'.sprintf('%03d',$mb_number).'.htm';
//	$fhandle = fopen($filename,'w');
//	fwrite($fhandle,$page);
//	fclose($fhandle);
	
	if(preg_match('/<h3.*?>(.*?)<\/h3>/is',$page,$match))
	{
		$title = $match[1];
		$title = strip_tags($title);
		$title = trim($title);
		$title = preg_replace('/\r\n/',' ',$title);
		
		echo 'title='.$title.'<br />';
	
		if(preg_match('/<ol.*?>(.*)<\/ol>/s',$page,$match))
		{
			$requirements_string = $match[1];
			$requirements_string =  add_req_tags($requirements_string);
			echo htmlspecialchars($requirements_string);
			$reqs = get_reqs($requirements_string);
			pre_print_r($reqs);
		}
	}
	if(is_array($reqs))
	{
		$result = write_mb($reqs,$title, $mb_number);
		echo $result;
	}
	else
	{
		echo '<span style="color: red;">Merit Badge Number '.$mb_number.' not found!</span><br />';
	}
}

function get_requirements_old($string)
{
	if(preg_match_all('/<li[^>]*?>([^<>]*?)(<ol[^>]*?>.*<\/ol>)?\s*<\/li>/s',$string,$match))
	{
		$requirements = $match[1];
		$sub_requirements = $match[2];
		$i=0;
		foreach ($requirements as $requirement)
		{
			$reqs[$i]['req'] = $requirement;
			if($sub_requirements[$i])
			{
				$reqs[$i]['sub_req'] = get_requirements($sub_requirements[$i]);
			}
			$i++;
		}
	}
	return $reqs;
}

function get_requirements($string)
{
	if(preg_match_all('/<li[^>]*?>([^<>]*?)(<ol[^>]*?>.*<\/ol>)?\s*<\/li>/s',$string,$match))
	{
		$requirements = $match[1];
		$sub_requirements = $match[2];
		$i=0;
		foreach ($requirements as $requirement)
		{
			$reqs[$i]['req'] = $requirement;
			if($sub_requirements[$i])
			{
				$reqs[$i]['sub_req'] = get_requirements($sub_requirements[$i]);
			}
			$i++;
		}
	}
	return $reqs;
}

function write_mb($reqs,$title,$mb_number)
{
	$sql = 'select id from award where mb_number = '.$mb_number;
	if(!do_query($sql,'scouts'))
	{
		$sql = 'INSERT into award set title = \''.addslashes($title).'\', type = \'Merit Badge\', mb_number = '.$mb_number;
		echo '<br />';
		if($_SESSION['TEST'] == true)
		{
			print_r($sql);
			echo '<br />';
			$rvalue = '<span style="color: red;">Merit Badge Number '.$mb_number.' not written (test)!</span><br />';
		}
		else
		{
			execute_query($sql,'scouts');
			$award_id = mysql_insert_id();
			$rvalue = '<span style="color: green;">Merit Badge Number '.$mb_number.' written!</span><br />';
		}
		write_requirements($reqs,$award_id,0);
		return $rvalue;
	}
	else
	{
		return '<span style="color: purple;">MB '.$mb_number.' already exists...skipping.</span><br />';
	}
}


function write_requirements($reqs,$award_id, $parent_id)
{
//	print_r($reqs);
//	echo '<br />';
	$num_string = Array('ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5, 'SIX' => 6, 'SEVEN' => 7, 'EIGHT' => 8, 'NINE' => 9, 'TEN' => 10);
	$number = 1;
	if(is_array($reqs))
	{
		foreach ($reqs as $req)
		{
			if(preg_match('/(one|two|three|four|five|six|seven|eight|nine|ten) of the following/i',$req['req'],$match))
			{
				$n_required = $num_string[strtoupper($match[1])];
			}
			else
			{
				$n_required = 0;
			}
			$sql = 'INSERT into requirement set award_id = '.$award_id.', number = '.$number.', description = \''.addslashes($req['req']).'\', n_required = '.$n_required.', parent_id = '.$parent_id;
			if($_SESSION['TEST'] == true)
			{
				print_r($sql);
				echo '<br />';
			}
			else
			{
				execute_query($sql,'scouts');
				$new_id = mysql_insert_id();
			}
			if($req['sub_req'])
			{
				write_requirements($req['sub_req'], $award_id, $new_id);
			}
			$number++;
		}
	}
	else
	{
		echo 'Not an array:';
		print_r($reqs);
		echo '<br />';
	}
	if(!($_SESSION['TEST'] == true))
	{
		UpdateRequirementNumbers($award_id);
	}
}

function http_get($host,$path, $port=80)
{
	$fp = fsockopen($host, $port, $errno, $errstr, 30);
	if (!$fp)
	{
	   echo "$errstr ($errno)<br />\n";
	}
	else
	{
	   $out = "GET ".$path." HTTP/1.1\r\n";
	   $out .= "Host: ".$host."\r\n";
	   $out .= "Connection: Close\r\n\r\n";
	
	   fwrite($fp, $out);
	   while (!feof($fp))
	   {
	       $rvalue .= fgets($fp, 128);
	   }
	   fclose($fp);
	}
	return $rvalue;
}

function add_req_tags($string)
{
	$string = preg_replace('/<i>/','',$string);
	$string = preg_replace('/<\/i>/','',$string);
	$string = preg_replace('/<b>/','',$string);
	$string = preg_replace('/<\/b>/','',$string);
	$string = preg_replace('/<p[^>]*?>/','',$string);
	$string = preg_replace('/<\/p>/','',$string);
	$string = preg_replace('/\r\n/s',' ',$string);
	$string = preg_replace('/\n/s',' ',$string);
	$string = preg_replace('/\s+/',' ',$string);
	$string = preg_replace('/<li[^>]*?>/','<li>',$string);
	$string = preg_replace('/<ol[^>]*?>/','<ol>',$string);
	$string = preg_replace('/<li>\s*([^<]*?)\s*<li>/s','<li>${1}</li><li>',$string);
	$string = preg_replace('/<li>\s*([^<]*?)\s*<li>/s','<li>${1}</li><li>',$string);
//	$string = preg_replace('/<li>\s*<b>([^<]*?)<\/b>([^<]*?)\s*<li>/s','<li><b>${1}</b>${2}</li><li>',$string);
//	$string = preg_replace('/<li>\s*<b>([^<]*?)<\/b>([^<]*?)\s*<li>/s','<li><b>${1}</b>${2}</li><li>',$string);
	$string = preg_replace('/<\/ol>\s*<li>/s','</ol></li><li>',$string);
//	$string = preg_replace('/<li[^>]*?\>(.*?)<ol/','<li>${1}<ol',$string);
	return $string;
}

function xml2array($data)
{
	$xml_parser = xml_parser_create();
	
	xml_parse_into_struct($xml_parser, $data, $vals, $index);
	xml_parser_free($xml_parser);
	
	$params = array();
	$level = array();
	$i=0;
	foreach ($vals as $xml_elem) {
	  if ($xml_elem['type'] == 'open') {
	   if (array_key_exists('attributes',$xml_elem)) {
	     list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
	   } else {
	     $level[$xml_elem['level']] = $xml_elem['tag'];
	   }
	  }
	  if ($xml_elem['type'] == 'complete') {
	   $start_level = 1;
	   $php_stmt = '$params';
	   while($start_level < $xml_elem['level']) {
	     $php_stmt .= '[$level['.$start_level.']]';
	     $start_level++;
	   }
	   $php_stmt .= '[$xml_elem[\'tag\'].\''.$i.'\'] = $xml_elem[\'value\'];';
	   echo htmlspecialchars($php_stmt).'<br />';
	   eval($php_stmt);
	   $i++;
	  }
	}
	
	echo "<pre>";
	print_r ($params);
	echo "</pre>";
}

class XMLParser
{
   var $path;
   var $result;

   function XMLParser($encoding, $data)
   {
       $this->path = "\$this->result";
       $this->index = 0;
      
       $xml_parser = xml_parser_create($encoding);
       xml_set_object($xml_parser, &$this);
       xml_set_element_handler($xml_parser, 'startElement', 'endElement');
       xml_set_character_data_handler($xml_parser, 'characterData');

       xml_parse($xml_parser, $data, true);
       xml_parser_free($xml_parser);
   }
  
   function startElement($parser, $tag, $attributeList)
   {
       eval("\$vars = get_object_vars(".$this->path.");");
       $this->path .= "->".$tag;
       if ($vars and array_key_exists($tag, $vars)) {
             eval("\$data = ".$this->path.";");
                 if (is_array($data))
                 {
                       $index = sizeof($data);
                       $this->path .= "[".$index."]";
                 }
                 else if (is_object($data))
                 {
                       eval($this->path." = array(".$this->path.");");
                       $this->path .= "[1]";
                 }
       }
       eval($this->path." = null;");

       foreach($attributeList as $name => $value)
           eval($this->path."->".$name. " = '".$value."';");
   }
  
   function endElement($parser, $tag)
   {
       $this->path = substr($this->path, 0, strrpos($this->path, "->"));
   }
  
   function characterData($parser, $data)
   {
       eval($this->path." = '".trim($data)."';");
   }
   
   function cleanString( $string )
   {
       return utf8_decode( trim( $string ) );
   }
}

function test($xml_data)
{
	$xml_data = preg_replace('/\s*\r\n\s*/',' ',$xml_data);
	$xml_data = preg_replace('/&quot;/','"',$xml_data);
	pre_print_r($xml_data);
	$xmlC = new XmlC();
	
	$xmlC->Set_XML_data( $xml_data );
	
	echo( "<pre>\n" );
	print_r( $xmlC->obj_data );
	echo( "</pre>\n" );
}

class XmlC
{
  var $xml_data;
  var $obj_data;
  var $pointer;

  function XmlC()
  {
  }

  function Set_xml_data( &$xml_data )
  {
   $this->index = 0;
   $this->pointer[] = &$this->obj_data;

   $this->xml_data = $xml_data;
   $this->xml_parser = xml_parser_create( "UTF-8" );

   xml_parser_set_option( $this->xml_parser, XML_OPTION_CASE_FOLDING, false );
   xml_set_object( $this->xml_parser, &$this );
   xml_set_element_handler( $this->xml_parser, "_startElement", "_endElement");
   xml_set_character_data_handler( $this->xml_parser, "_cData" );

   xml_parse( $this->xml_parser, $this->xml_data, true );
   xml_parser_free( $this->xml_parser );
  }

  function _startElement( $parser, $tag, $attributeList )
  {
   foreach( $attributeList as $name => $value )
   {
     $value = $this->_cleanString( $value );
     $object->$name = $value;
   }
   eval( "\$this->pointer[\$this->index]->" . $tag . "[] = \$object;" );
   eval( "\$size = sizeof( \$this->pointer[\$this->index]->" . $tag . " );" );
   eval( "\$this->pointer[] = &\$this->pointer[\$this->index]->" . $tag . "[\$size-1];" );
  
   $this->index++;
  }

  function _endElement( $parser, $tag )
  {
   array_pop( $this->pointer );
   $this->index--;
  }

  function _cData( $parser, $data )
  {
   if( trim( $data ) )
   {
     $this->pointer[$this->index] = trim( $data );
   }
  }

  function _cleanString( $string )
  {
   return utf8_decode( trim( $string ) );
  }
  
}

function get_reqs($string)
{
	$i = 1;
	$li_pos = get_start_end_positions($string,'li');
//	pre_print_r($li_pos);
	$start_next = true;
	$depth = 0;
	foreach ($li_pos as $pos => $type)
	{
		if($start_next == true)
		{
			$start = $pos;
			$start_next = false;
		}
		if($type == 'start')
		{
			$depth++;
		}
		else
		{
			$depth--;
		}
		if($depth == 0)
		{
			$li[$i++] = substr($string,$start,$pos+5-$start);
			$start_next = true;
		}
	}
	if($depth != 0)
	{
		echo '<span style="color: red;"><br />Warning: non-matching tags at start='.$start.': ('.htmlspecialchars(substr($string,$start,50)).'...)</span><br />';
		$_SESSION['TEST'] = true; // don't write any data to the database
		$li[$i++] = substr($string,$start);
	}
	foreach ($li as $iter => $value)
	{
		if(preg_match('/<li>([^<]*?)(<.*)/',$value,$match))
		{
			$req[$iter]['req'] = $match[1];
		}
		if(preg_match('/<ol>(.*)<\/ol>/',$value,$match))
		{
			$req[$iter]['sub_req'] = get_reqs($match[1]);
		}
	}
	return $req;
}

function get_start_end_positions($string,$tag_name)
{
	$tag_pos['start'] = get_positions($string,'<'.$tag_name.'>');
//	pre_print_r($tag_pos['start']);
	$tag_pos['end'] = get_positions($string,'</'.$tag_name.'>');
//	pre_print_r($tag_pos['end']);
	foreach ($tag_pos['start'] as $start)
	{
		$tags[$start] = 'start';
	}
	if(is_array($tag_pos['end']))
	{
		foreach ($tag_pos['end'] as $end)
		{
			$tags[$end] = 'end';
		}
	}
	ksort($tags);
//	pre_print_r($tags);
	return $tags;
}

function get_positions($buffer, $tag)
{
//	echo 'tag='.htmlspecialchars($tag).'<br />';
	$position = -1;
//	echo $buffer;
	while(($position = stripos($buffer,$tag, $position+1)) !== false)
	{
		$tag_pos[] = $position;
//		echo 'position for '.htmlspecialchars($tag).' = '.$position.'<br />';
//		$buffer = substr($buffer,$position);
//		echo $buffer;
	}
	return $tag_pos;
}

?>