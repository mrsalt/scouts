<?php

require_once 'globals.php';

class PageTemplate
{
	var $page_title;
	var $body_tags = Array();
	var $body_styles = Array();
	var $menu_links = Array();
	var $scripts = Array();
	var $menu_color = '#F0F0F4';
	var $menu_sel_color = '#DDDDE8';
	var $styles = Array();
	var $style_sheets = Array();
	
	function PageTemplate()
	{
		$this->addStyle('a.menu_style','color: darkblue; text-decoration:none; font-weight:bold;');
		$this->addStyle('a.menu_style:visited','color: darkblue; text-decoration:none; font-weight:bold;');
		$this->addStyle('a.menu_style:hover','background: white; color: blue;     text-decoration:none; font-weight:bold;');
		$this->addStyle('td.menu_node','border: 1px solid black; padding: 6px 10px 6px 10px; -moz-border-radius: 7px 7px 0px 0px;');
	}
	
	function addScript($src)
	{
		$this->scripts[] = $src;	
	}
	
	function setBodyTag($tag, $value)
	{
		$this->body_tags[$tag] = $value;
	}
	
	function setBodyStyle($style, $value)
	{
		$this->body_styles[$style] = $value;
	}
	
	function setPageTitle($title)
	{
		$this->page_title = $title;	
	}
	
	function setLink($key, $name, $url)
	{
		$this->menu_links[$key] = Array('name' => $name, 'url' => $url);
	}
	
	function addStyle($style_name, $definition)
	{
		$this->styles[$style_name] = $definition;	
	}
	
	function addStyleSheet($url)
	{
		$this->style_sheets[] = $url;
	}
	
	function startHTML()
	{
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">\n";
		echo "<html>\n";
	}
	
	function writeHead($add_text = '')
	{
		echo "<head><title>".$this->page_title."</title>\n";
		foreach ($this->scripts as $src)
			echo '<script type="text/javascript" language="javascript" src="'.$src.'"></script>'."\n";
		echo $add_text."\n";
		if (count($this->style_sheets))
		{		
  		foreach ($this->style_sheets as $url)
  			echo '<link rel="stylesheet" type="text/css" href="'.$url.'" />'."\n";
		}
		if (count($this->styles))
		{		
			echo '<style type="text/css">'."\n";
  		foreach ($this->styles as $name => $style)
  			echo "  $name { $style }\n";
			echo '</style>'."\n";
		}
//		echo '<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />'."\n";
		echo "</head>\n";
	}
	
	function startBody()
	{
		echo "<body";
		if (count($this->body_tags)) // onLoad="" onUnload=""
		{
			foreach ($this->body_tags as $tag => $value)
			{
				echo " $tag=\"$value\"";
			}
		}
		if (count($this->body_styles))
		{
			echo " style=\"";
			foreach ($this->body_styles as $tag => $value)
			{
				echo "$tag:$value;";
			}
			echo '"';
		}
		echo ">\n";
	}
	
	function endBody()
	{
		echo "</body>\n";
	}
	
	function endHTML()
	{
		echo "</html>	\n";
	}
	
	function writeMenu()
	{
		//$node_style = 'border: 1px solid black; padding: 6 10 6 10; background-image: url(\'images/header_blank.jpg\'); background-repeat: repeat;';
//		$node_style = 'background-color: '.$this->menu_color.';';

		$columns = 0;
		echo '<table style="position: relative; top: 4px; empty-cells: show;" cellspacing=0 cellpadding=2 width="100%">'."\n";
		echo '<tr style="width: 100%;">'."\n";
		echo '<td style="font-size: 1%; border-bottom: 1px solid black;">.</td>'."\n";// font-size/color hack to make empty-cells: show work in IE without border-collapse: collapse;
		echo '<td style="font-size: 1%; border-bottom: 1px solid black;">.</td>'."\n";// font-size/color hack to make empty-cells: show work in IE without border-collapse: collapse;
		$columns += 2;
		foreach ($this->menu_links as $key => $link)
		{
			if(basename($_SERVER['PHP_SELF']) == $link['url'])
			{
				$bgColor = $this->menu_sel_color;
			}
			else
			{
				$bgColor = $this->menu_color;
			}
			// onMouseOver="style.backgroundColor=\'green\';"
			echo '<td class="menu_node" style="background-color: '.$bgColor.'; '.($bgColor == $this->menu_sel_color ? 'border-bottom: 0px;' : '' ) . '" nowrap onMouseOver="style.backgroundColor=\'white\';" onMouseOut="style.backgroundColor=\''.$bgColor.'\';"><a class="menu_style" href="'.$link['url'].'">'.$link['name'].'</a></td>'."\n";
			echo '<td style="border-bottom: 1px solid black; color: '.$bgColor.'; font-size: 1%;">.</td>'."\n"; // font-size/color hack to make empty-cells: show work in IE without border-collapse: collapse;
			$columns += 2;
			//echo '<td background=white width=1></td>';
		}
		echo '<td width="100%" style="border-bottom: 1px solid black;">&nbsp;</td>'."\n";
		$columns++;
		echo '</tr>';
		echo '<tr><td colspan="'.$columns.'" style="height: 10px; background-color: '.$this->menu_sel_color.';"></td></tr>'."\n";
		echo '</table>'."\n";
		echo '<div id="main_content" style="padding: 10px 0px 10px 0px;">'."\n";  // border-style: solid;
	}
	
	function writeFooter()
	{
		echo '</div>';
		PageTemplate::endBody();
		PageTemplate::endHTML();
	}
}

?>