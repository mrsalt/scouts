<?php

class PictureGallery
{
	private $group_id, $manage_privilege;
	private $picture_table, $category_table, $database;
	private $manage = '';
	
	public function __construct($group_id, $manage_privilege, $picture_table, $category_table, $group_table, $database)
	{
		$this->group_id = $group_id;
		$this->manage_privilege = $manage_privilege;
		$this->picture_table = $picture_table;
		$this->category_table = $category_table;
		$this->group_table = $group_table;
		$this->database = $database;
	}
	
	public function handleRequest()
	{
		$this->setSessionVariables();
		
		if(($_REQUEST['manage'] == 'pictures' || $_REQUEST['manage'] == 'categories') && $this->manage_privilege)
		{
			$this->manage = $_REQUEST['manage'];
		}
		
		if(($_POST['commit_changes'] == 1) && $this->manage_privilege)
		{
			// Delete Pictures & change captions
			$pictures = $this->getPictures($_SESSION['category_id']);
			$picture_id_list = array_field($pictures,'id');
			foreach ($_POST as $name => $value)
			{
				if(preg_match('/delete_(\d+)/',$name, $match) and $value == 'on') // if it's checked to be deleted
				{
					$delete_id = $match[1];
					if(in_array($delete_id,$picture_id_list)) // only allow delete for pictures in this category and group_id
					{
						// delete the picture
						$this->deletePicture($delete_id);
					}
				}
				else if(preg_match('/^caption_(\d+)/',$name, $match))
				{
					$caption_id = $match[1];
					if(in_array($caption_id,$picture_id_list)) // only allow modifications for pictures in this category and group_id
					{
						$this->addCaption($caption_id, $value);
					}
				}
				else if(preg_match('/^category_caption_(\d+)/',$name, $match))
				{
					$category_id = $match[1];
					$categories = array_field(fetch_array_data('select category_id from '.$this->group_table.' where group_id = '.$this->group_id, $this->database),'category_id');
					if(in_array($category_id, $categories))
					{
						$this->addCategoryCaption($category_id, $value);
					}
				}
			}
		}
		else if($_GET['delete_category'] and ctype_digit((string)$_GET['delete_category']) and $this->manage_privilege and in_array($_GET['delete_category'], array_field(fetch_array_data('select category_id from '.$this->group_table.' where group_id = '.$this->group_id.' order by category_id desc',$this->database),'category_id')))
		{
			$pictures = fetch_array_data('select id from '.$this->picture_table.' where category_id = '.$_GET['delete_category'],$this->database,'id');
			foreach ($pictures as $picture)
			{
				// delete the picture
				$this->deletePicture($picture['id']);
//				unlink($this->getPicturePath($picture['id']));
//				execute_query('delete from '.$this->picture_table.' where id = '.$picture['id'],$this->database);
			}
//			if(!do_query('select id from '.$this->picture_table.' where category_id = '.$_GET['delete_category'],$this->database))
//			{
//				$this->deleteCategory($_GET['delete_category']);
//			}

			// deletePicture() will actually take care of deleting the category when the last picture is deleted in the category.
			echo '<br /><center>Category Successfully Deleted!</center><br />';
		}
		else if($_REQUEST['commit_category_groups'] == 1)
		{
			$troop_id = do_query('select troop_id from scout_group where id = '.$this->group_id, $this->database);
			$legal_groups = fetch_array_data('select id from scout_group where troop_id = '.$troop_id, $this->database, 'id');
			$legal_categories = fetch_array_data('select category_id from scout_picture_group where group_id in ('.join(',', array_field($legal_groups,'id')).')',$this->database, 'category_id');
			$category_groups = $_REQUEST['category_group_value'];
			foreach ($category_groups as $category_id => $category_group)
			{
				if($legal_categories[$category_id])
				{
					foreach ($category_group as $group_id => $value)
					{
						if($legal_groups[$group_id])
						{
							if($value == 'T')
							{
								if(!do_query('select category_id from scout_picture_group where group_id = '.$group_id.' and category_id = '.$category_id, $this->database))
								{
									execute_query('insert into scout_picture_group set category_id = '.$category_id.', group_id = '.$group_id,$this->database);
								}
							}
							else if($value == 'F')
							{
								if(do_query('select group_id from scout_picture_group where category_id = '.$category_id.' and group_id != '.$group_id, $this->database))
								{
									if(do_query('select category_id from scout_picture_group where group_id = '.$group_id.' and category_id = '.$category_id, $this->database))
									{
										execute_query('delete from scout_picture_group where group_id = '.$group_id.' and category_id = '.$category_id,$this->database);
									}
								}
								else
								{
									$error_msg .= 'Error: Unable to remove group "'.do_query('select group_name from scout_group where id = '.$group_id, $this->database).'" from category "'.do_query('select name from '.$this->category_table.' where id = '.$category_id, $this->database).'" because it is the last group associated with this category.  Please Use the Manage Pictures Link to delete a category.<br />';
								}
							}
							else
							{
								$error_msg .= 'Error: Invalid value='.$value.'<br />';
							}
						}
					}
				}
			}
			if($error_msg)
			{
				echo $error_msg;
			}
			else
			{
				echo 'Settings Saved!<br />';
			}
		}
	}
	
	public function getManageValue()
	{
		return $this->manage;
	}
	
	public function getPicturePageUrl($picture_id, $width, $height)
	{
		return 'pictures.php?picture_id='.$picture_id.'&amp;height='.$height.'&amp;width='.$width;
	}
	
	public function getResizeUrl($picture_filename, $width, $height, $escape=true)
	{
		if($escape)
		{
			$and = '&amp;';
		}
		else
		{
			$and = '&';
		}
		if($width && $height)
		{
			return 'resize.php?picture='.$picture_filename.$and.'height='.$height.$and.'width='.$width;
		}
		else
		{
			return $picture_filename;
		}
	}
	
	public function getPicturePage($picture_id, $height, $width)
	{
		if(!$this->can_view_picture($picture_id))
		{
			$rvalue = 'Error: You do not have privileges to view this picture_id ('.$picture_id.').';
			return $rvalue;
		}
		$filename = do_query('select filename from '.$this->picture_table.' where id = '.$picture_id,$this->database);
		preg_match('/(.*?)\.jpg/i',$filename,$match);
		$title = $match[1];
	
		$rvalue .= "<center><h1>$title</h1></center>";
		$rvalue .= "<center>" . $this->getPictureLinks($picture_id) . "</center>";
		$rvalue .= "<center>";
		$rvalue .= '<img style="border: 0px;" src="'.$this->getResizeUrl($this->getPicturePath($picture_id), $width, $height).'" />';

		$caption = $this->getCaption($picture_id);
		if($caption)
		{
			$rvalue .= '<br />';
			$rvalue .= '<span style="font-size: 80%;">'.$caption.'</span>';
		}
		$rvalue .= "</center>";
		return $rvalue;
	}
	
	public function getCategoryPage($category_id)
	{
		$categories = fetch_array_data('select id as value, name from '.$this->category_table.', '.$this->group_table.' where '.$this->category_table.'.id = '.$this->group_table.'.category_id and '.$this->group_table.'.group_id = '.$this->group_id.' order by id desc',$this->database);

		if(!count($categories))
		{
				$rvalue .= '<br /><center>No pictures have been uploaded yet!</center>';
				if($this->manage_privilege)
				{
					$rvalue .= '<br /><br /><a href="pictures.php?upload_pictures=1">[Upload Pictures]</a>';
				}
				return $rvalue;
		}
		if($category_id)
		{
			$category_name = do_query('select name from '.$this->category_table.' where id = '.$category_id,$this->database);
			$categoryCaption = $this->getCategoryCaption($category_id);
		}
		$rvalue .= "<center><h1>".$category_name."</h1></center><br />";
		$rvalue .= "<center>";
		
		$rvalue .= '<form action="pictures.php" method="get">';
		$rvalue .= ShowSelectList('category_id',$category_id,$categories,'location.assign(\'pictures.php?category_id=\'+this.value)');
		$rvalue .= '<input type="submit" value="Go" />';
		$rvalue .= '</form>';
		if($categoryCaption)
		{
			$rvalue .= $categoryCaption;
		}
	
		$rvalue .= '</center>';
		$rvalue .= "<br />";
		$rvalue .= "<hr/>";
	
		$images = $this->getPictures($category_id);
		$rvalue .= "<div>";
		if($this->manage == 'pictures')
		{
			$rvalue .= '<center><button onclick="if(confirm(\'Are you sure you want to delete this category along with all the pictures in it?\')) location.assign(\'pictures.php?delete_category='.$category_id.'\');" >Delete Category</button></center>';
			$rvalue .= '<form method="post" action="pictures.php" >';
			$rvalue .= '<input type="hidden" name="commit_changes" value="1" />';
			$categoryCaption = $this->getCategoryCaption($category_id);
			$rvalue .= '<br /><center><div>Category Caption:<br />';
			$rvalue .= '<input name="category_caption_'.$category_id.'" type="text" size="100" maxlength="255" value="'.$categoryCaption.'" /></div></center><br /><br />';
		}
		foreach ($images as $image)
		{
			preg_match('/^.*\/(.*?)\.(jpg)/i',$image['filename'],$match);
			
			$name = $match[1];
			$ext = $match[2];
			if($ext == 'jpg' or $ext == 'JPG')
			{
				$rvalue .= '<div class="'.(preg_match('/pano/i',$name) ? 'pano' : 'picture').'" >';
				if(!$this->manage == 'pictures')
				{
					$rvalue .= "<font size=1>";
					$rvalue .= "<a href=\"" . $this->getPicturePageUrl($image['id'], 640, 480) . "\">640x480</a>";
					$rvalue .= "&nbsp;&nbsp;";
					$rvalue .= "<a href=\"" . $this->getPicturePageUrl($image['id'], 800, 600) ."\">800x600</a>";
					$rvalue .= "&nbsp;&nbsp;";
					$rvalue .= "<a href=\"" . $this->getPicturePageUrl($image['id'], 1024, 768) . "\">1024x768</a>";
					$rvalue .= "&nbsp;&nbsp;";
					$rvalue .= "<a href=\"" . $this->getPicturePageUrl($image['id'], 0, 0) . "\">Actual</a>";
					$rvalue .= "</font>";
				}
				$rvalue .= '<a href="' . $this->getPicturePageUrl($image['id'], 800, 600) . '"><img style="border: 0px;" src="'.$this->getResizeUrl($this->getPicturePath($image['id']), 200, 200).'" /></a><br />';
				$rvalue .= "<b>$name</b><br />";
				$caption = $this->getCaption($image['id']);
				if(!$this->manage == 'pictures')
				{
					if($caption)
					{
						$rvalue .= '<br />';
						$rvalue .= '<span style="font-size: 80%;">'.$caption.'</span>';
					}
				}
				if($this->manage == 'pictures')
				{
					$rvalue .= '<input type="checkbox" name="delete_'.$image['id'].'" /> <span style="color: red;">Delete</span>';
					$rvalue .= '<br />';
					$rvalue .= '<div align="left">Caption:<br />';
					$rvalue .= '<input name="caption_'.$image['id'].'" type="text" size="28" maxlength="60" value="'.$caption.'" /></div>';
				}
				$rvalue .= "</div>";
			}
		}
		$rvalue .= '</div><div class="spacer">';
		if($this->manage == 'pictures')
		{
			$rvalue .= '<br /><input type="submit" />';
			$rvalue .= '</form>';
		}
		$rvalue .= '</div>';
		$rvalue .= $this->getEndOfCategoryPage();
		return $rvalue;
	}
	
	public function getCategoryManagePage()
	{
		$troop_id = do_query('select troop_id from scout_group where id = '.$this->group_id, $this->database);
		$troop_groups = array_field(fetch_array_data('select id from scout_group where troop_id = '.$troop_id, $this->database, 'id'), 'id');
		$categories = fetch_array_data('select distinct '.$this->category_table.'.id, name from '.$this->category_table.', '.$this->group_table.' where '.$this->category_table.'.id = '.$this->group_table.'.category_id and group_id in ('.join(',',$troop_groups).')', $this->database);
		$groups = fetch_array_data('select scout_group.id, group_name from scout_group, user where user.scout_troop_id = scout_group.troop_id and user.id = '.$_SESSION['USER_ID'], $this->database);
		$rvalue .= '<table style="border-collapse: collapse; empty-cells: show;">';
		$rvalue .= '<tr>';
		foreach ($groups as $group)
		{
			$rvalue .= '<td class="header">';		
			$rvalue .= '<img src="text_image.php?string='.$group['group_name'].'&amp;background-color=000000&amp;color=FFFFFF&amp;rotate=270&amp;x=5&amp;y=3&font-size=3&amp;width=190&amp;height=20" />';
			$rvalue .= '</td>';
		}
		$rvalue .= '</tr>';
		foreach ($categories as $category)
		{
			$rvalue .= '<tr>';
			$selected_groups = fetch_array_data('select group_id from '.$this->group_table.' where category_id = '.$category['id'], $this->database, 'group_id');
			foreach ($selected_groups as $selected_group_id => $value)
			{
				$category_group[$category['id']][$selected_group_id] = 'T';
			}
			foreach ($groups as $group)
			{
				$rvalue .= '<td class="value"><input type="checkbox" onclick="setCategoryGroup('.$category['id'].', '.$group['id'].', this.checked ? \'T\' : \'F\')" name="category_'.$category['id'].'_group_'.$group['id'].'" '.($category_group[$category['id']][$group['id']] ? 'checked="checked" ' : '').'/></td>';
			}
			$rvalue .= '<td class="value">'.$category['name'].'</td>';
			$rvalue .= '</tr>';
		}
		$rvalue .= '</table>';
		
		$original_values =  json_encode($category_group);
		$rvalue .= "\n<script type=\"text/javascript\">\n";
		$rvalue .= "var original_category_group_value = ".$original_values.";\n";
		$rvalue .= "var new_category_group_value = ".$original_values.";\n";
		$rvalue .= "function setCategoryGroup(category_id, group_id, value)\n".
		           "{\n".
		           "	new_category_group_value[category_id][group_id] = value;\n".
		           "}\n".
		           "function getCategoryGroupParams()\n".
		           "{\n".
		           "	var add_sep = '';\n".
		           "	var remove_sep = '';\n".
		           "	var add_params = '';\n".
		           "	var remove_params = '';\n".
		           "	var params = '';\n".
		           "	for(category_id in new_category_group_value)\n".
		           "	{\n".
		           "		for(group_id in new_category_group_value[category_id])\n".
		           "		{\n".
		           "			if(new_category_group_value[category_id][group_id] == 'T' && !original_category_group_value[category_id][group_id])\n".
		           "			{\n".
		           "				add_params += add_sep + 'category_group_value['+category_id+']['+group_id+']='+new_category_group_value[category_id][group_id];\n".
		           "				add_sep = '&';\n".
		           "			}\n".
		           "			else if(new_category_group_value[category_id][group_id] == 'F' && original_category_group_value[category_id][group_id])\n".
		           "			{\n".
		           "				remove_params += remove_sep + 'category_group_value['+category_id+']['+group_id+']='+new_category_group_value[category_id][group_id];\n".
		           "				remove_sep = '&';\n".
		           "			}\n".
		           "		}\n".
		           "	}\n".
		           "	params = add_params + add_sep + remove_params;\n".
		           "	return params;\n".
		           "}\n".
		           "</script>\n";
		
		$rvalue .= '<br /><button onclick="location.assign(\'pictures.php?manage=categories&commit_category_groups=1&\'+getCategoryGroupParams())">Apply Changes</button>';
		$rvalue .= $this->getEndOfCategoryPage();
		return $rvalue;
	}
	
	private function getEndOfCategoryPage()
	{
		if($this->manage_privilege)
		{
			$rvalue .= '<br /><br /><a href="pictures.php?upload_pictures=1">[Upload Pictures]</a>';
			if($this->manage == 'pictures')
			{
				$rvalue .= '&nbsp;&nbsp;<a href="pictures.php">[Exit Manage Pictures]</a>';
			}
			else if($this->manage == 'categories')
			{
				$rvalue .= '&nbsp;&nbsp;<a href="pictures.php">[Exit Manage Categories]</a>';
			}
			else
			{
				$rvalue .= '&nbsp;&nbsp;<a href="pictures.php?manage=pictures">[Manage Pictures]</a>';
				$rvalue .= '&nbsp;&nbsp;<a href="pictures.php?manage=categories">[Manage Categories]</a>';
			}
		}
		return $rvalue;
	}
	
	private function getPictures($category_id)
	{
		$pictures = fetch_array_data('select id from '.$this->picture_table.' where category_id = '.$category_id,$this->database,'id');
		if(is_array($pictures))
		{
			foreach($pictures as &$picture)
			{
				$picture['filename'] = $this->getPicturePath($picture['id']);
			}
		}
		return $pictures;
	}
	
	public function getPictureLinks($picture_id)
	{
		$category_id = do_query('select category_id from '.$this->picture_table.' where id = '.$picture_id,$this->database);
		if($prev = do_query('select max(id) from '.$this->picture_table.' where id < '.$picture_id.' and category_id = '.$category_id,$this->database))
		{
			$rtext .= '<a href="pictures.php?picture_id='.$prev.'"><img style="border: 0px;" src="images/navigation-back.gif" /></a>';
		}
		else
		{
			$rtext .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$rtext .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		$rtext .= '<a href="pictures.php">Back to Gallery</a>';
		$rtext .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		if($next = do_query('select min(id) from '.$this->picture_table.' where id > '.$picture_id.' and category_id = '.$category_id,$this->database))
		{
			$rtext .= '<a href="pictures.php?picture_id='.$next.'"><img style="border: 0px;" src="images/navigation-next.gif" /></a>';
		}
		else
		{
			$rtext .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		return $rtext;
	}
	
	public function can_view_picture($picture_id)
	{
		$category_id = do_query('select category_id from '.$this->picture_table.' where id = '.$picture_id,$this->database);
		if($category_id)
		{
			return $this->can_view($category_id);
		}
		return false;
	}
	
	public function can_view($category_id)
	{
		if(do_query('select group_id from '.$this->group_table.' where category_id = '.$category_id,$this->database) == $this->group_id)
		{
			return true;
		}
		return false;
	}
	
	public function getPicturePath($picture_id)
	{
		$picture_info = fetch_data('select filename, category_id from '.$this->picture_table.' where id = '.$picture_id, $this->database);
		return 'pictures/'.$picture_info['category_id'].'/'.$picture_info['filename'];
	}
	
	private function deleteCategory($category_id)
	{
		execute_query('delete from '.$this->category_table.' where id = '.$category_id,$this->database);
		execute_query('delete from '.$this->group_table.' where category_id = '.$category_id,$this->database);
		$_SESSION['category_id'] = do_query('select category_id from '.$this->group_table.' where group_id = '.$this->group_id.' order by category_id desc',$this->database);
	}
	
	public function deletePicture($picture_id)
	{
		unlink($this->getPicturePath($picture_id));
		$category_id = do_query('select category_id from '.$this->picture_table.' where id = '.$picture_id, $this->database);
		execute_query('delete from '.$this->picture_table.' where id = '.$picture_id,$this->database);
		
		if(!do_query('select id from '.$this->picture_table.' where category_id = '.$category_id,$this->database))
		{
			// need to delete the category since there's nothing in it
			$this->deleteCategory($category_id);
		}
	}
	
	private function addCaption($picture_id, $caption)
	{
		execute_query('update '.$this->picture_table.' set caption = \''.htmlspecialchars(addslashes($caption)).'\' where id = '.$picture_id,$this->database);
	}
	
	private function addCategoryCaption($category_id, $caption)
	{
		execute_query('update '.$this->category_table.' set caption = \''.htmlspecialchars(addslashes($caption)).'\' where id = '.$category_id,$this->database);
	}
	
	private function setSessionVariables()
	{
		if($_REQUEST['category_id'] && ctype_digit((string)$_REQUEST['category_id']))
		{
			$_SESSION['category_id'] = $_REQUEST['category_id'];
		}
		
		// make sure the category id is valid.
		if(!$_SESSION['category_id'] || !do_query('select category_id from '.$this->group_table.' where category_id = '.$_SESSION['category_id'].' and group_id = '.$this->group_id,$this->database))
		{
			$_SESSION['category_id'] = do_query('select category_id from '.$this->group_table.' where group_id = '.$this->group_id.' order by category_id desc',$this->database);
		}

		if(isset($_REQUEST['height']))
		{
			$_SESSION['height'] = $_REQUEST['height'];
		}
		if(isset($_REQUEST['width']))
		{
			$_SESSION['width'] = $_REQUEST['width'];
		}
	}
	
	public function getUploadPage($category_id)
	{
		if(!$this->manage_privilege)
		{
			$rvalue .= 'Error: You do not have privileges to upload pictures.<br /><br />';
			$rvalue .= '<a href="pictures.php?">Back to Gallery</a>';
			return $rvalue;
		}
		if(!$category_id)
			$category_id = 0;
		$rvalue .= "<script type=\"text/javascript\" src=\"scripts/multifile.js\"></script>\n";
	//    $rvalue .= "<center>\n";
		$rvalue .= "<br /><br />\n";
    
		$rvalue .= '<form method="post" action="pictures.php?upload_pictures=1" enctype="multipart/form-data" onsubmit="return ValidateAllFields(this)">';

		$rvalue .= "<table style=\"color: white;\" border=0 bgcolor=\"black\" cellpadding=5 cellspacing=0>\n";
    $rvalue .= "<tr><td colspan=\"2\"><center><b>[Upload Pictures]</b>\n";
		$rvalue .= "<hr noshade size=1>\n";
		$rvalue .= "</td></tr>\n";
    #------------- Existing Category
    $rvalue .= "<tr>\n";
    $rvalue .= "<td align=\"right\">\n";
    $rvalue .= "Existing Category\n";
    $rvalue .= "</td>\n";
    
    $rvalue .= "<td>\n";

    $categories = fetch_array_data('select id as value, name from '.$this->category_table.', '.$this->group_table.' where '.$this->category_table.'.id = '.$this->group_table.'.category_id and '.$this->group_table.'.group_id = '.$this->group_id,$this->database);
    $categories[] = Array('name' => '&lt; New Category &gt;', 'value' => 0);
    $onChangeHandler = 'if(this.value != 0) {document.getElementById(\'new_category_row\').style.display = \'none\'; } else {document.getElementById(\'new_category_row\').style.display = \'\'; }';
    $rvalue .= ShowSelectList('category_id',$category_id,$categories, $onChangeHandler);
    $rvalue .= "</td>\n";
    $rvalue .= "</tr>\n";

    #------------- New Category
    $rvalue .= "<tr id=\"new_category_row\" " . ($category_id ? "style=\"display: none;\"" : '' ) . " >\n";
    $rvalue .= "<td align=\"right\">\n";
    $rvalue .= " (or) New Category\n";
    $rvalue .= "</td>\n";
    
    $rvalue .= "<td>\n";

    $rvalue .= '<input name="newCategory" type="text" size="20" />';
    $rvalue .= "</td>\n";
    $rvalue .= "</tr>\n";

    #------------- upload
    $rvalue .= "<tr>\n";
    $rvalue .= "<td align=\"right\">\n";
    $rvalue .= "Pictures (limit 10):\n";
    $rvalue .= "</td>\n";
    
    $rvalue .= "<td>\n";
		$rvalue .= '<input id="first_file_element" type="file" />';
    $rvalue .= "</td>\n";
    $rvalue .= "</tr>\n";
    
    $rvalue .= "<tr>\n";
    $rvalue .= "<td align=\"right\" colspan=\"2\">\n";
    $rvalue .= "<div id=\"files_list\"></div>\n";
    $rvalue .= "</td></tr>\n";
    
    $rvalue .= "<script type=\"text/javascript\">\n";
    $rvalue .= "var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 10 );\n";
    $rvalue .= "multi_selector.addElement( document.getElementById( 'first_file_element' ) );\n";
    $rvalue .= "</script>\n";



    #------------- submit
    $rvalue .= "<tr>\n";
    $rvalue .= "<td colspan=2 align=\"center\">\n";
    $rvalue .= "<hr noshade size=1>\n";

		$rvalue .= '<input type="submit" name="Upload" value="Upload" />';
    $rvalue .= "</td>\n";
    $rvalue .= "</tr>\n";

		$rvalue .= '</form>';

    $rvalue .= "</table>\n";
    $rvalue .= '<script type="text/javascript">'."\n";
    $rvalue .= 'function ValidateAllFields(form)'."\n";
    $rvalue .= '{'."\n";
    $rvalue .= '	if(form.category_id.value == 0 && !form.newCategory.value)'."\n";
    $rvalue .= '	{'."\n";
    $rvalue .= '		alert(\'You must select an existing category or enter a new one!\');'."\n";
    $rvalue .= '		return false;'."\n";
    $rvalue .= '}'."\n";
    $rvalue .= 'return true;'."\n";
    $rvalue .= '}'."\n";
    $rvalue .= '</script>'."\n";
    $rvalue .= '<a href="pictures.php?">Back to Gallery</a>';
//    $rvalue .= "</center>\n";
		return $rvalue;
	}
	
	private function uploadErrorCheck()
	{
		$g_overwrite=0;

		# if you want to restrict upload to files with certain extentions, change
		# the value of $g_restrict_by_ext=1 ad ALSO modify the $g_allowed_ext if you
		# wat to add other allowable extensions.
		$g_restrict_by_ext=1;
		$g_allowed_ext= Array("jpg","JPG");
		
		
		#-------------- globals---------- STARTS ------------------
		$g_debug=0;
	//		echo '<pre>';
	//    print_r($_FILES);
	//    print_r($_POST);
	//    print_r($_SESSION);
	//    echo '</pre>';
	    ##################
	    $em='';
	    ##################
	
			
	    #  check if the necessary fields are empty or not
	    if(!$_POST['category_id'] and !$_POST['newCategory'])
	    {
	    	$em .= "You must specify the Category!<br>";
	    }
	    foreach ($_FILES as $file)
	    {
		    if($file['name'])
		    {
		    	$file_exists = true;
		    	
			    # if you want to restrict upload to files with certain extention
			    if ($g_restrict_by_ext == 1)
			    {
			        $ta=preg_split('/\./',$file['name']);
			        $sz=count($ta);
			        if ($sz > 1)
			        {
			            $ext=$ta[$sz-1];
			            if (!in_array($ext,$g_allowed_ext))
			            {
			                $this->echoError("You are not allowed to upload this file (".htmlspecialchars($file['name']).". Extensions of type '".htmlspecialchars($ext)."' are not allowed!");
			                return false;
			            }
			
			        }
			        else
			        {
			            $this->echoError("You are not allowed to upload this file (".htmlspecialchars($file['name']).").  It does not have an extension.");
			             return false;
			        }
			    }
			  }
		  }
		  if(!$file_exists)
		  {
		  	$em .= "You must select a file to upload!<br />";
		  }
	
	    if ($em)
	    {
	        $this->echoError($em);
	        return false;
	    }
	
	    if (!$this->manage_privilege)
	    {
	        $this->echoError("Will not upload! You are not authorized to upload pictures.  manage_privilege=" . ($this->manage_privilege ? 'true' : 'false'));
	        return false;
	    }
	
	    if ($g_debug == 1)
	    {
	    		echo 'GET:<br />';
	        echo_r($_GET);
	        echo '<br /><br />';
	        echo 'POST:<br />';
	        echo_r($_POST);
	    }
	    return true;
	}
	
	public function uploadPictures()
	{
		if(!$this->uploadErrorCheck())
		{
			return;
		}
			foreach ($_FILES as $file => $values)
			{
				$filepath=$values['name'];
				if($filepath)
				{
					$size=$values['size'];
			
			    # James Bee" <JamesBee@home.com> reported that from Windows filename
			    # such as c:\foo\fille.x saves as c:\foo\file.x, so we've to get the
			    # filename out of it
			    # look at the last word, hold 1 or more chars before the end of the line
			    # that does't include / or \, so it will take care of unix path as well
			    # if it happes, muquit, Jul-22-1999
			    if (preg_match('/([^\\/\\\]+)$/',$filepath,$match))
			    {
			        $filename=$match[1];
			    }
			    else
			    {
			        $filename="$filepath";
			    }
			    # if there's ay space in the filename, get rid of them
			    $filename = preg_replace('/\s+/','',$filename);
			    
			    if($_POST['category_id'])
			    {
			    	$category_id = $_POST['category_id'];
			    }
			    else
			    {
			    	$category_id = do_query('select category_id from '.$this->category_table.', '.$this->group_table.' where '.$this->category_table.'.id = '.$this->group_table.'.category_id and '.$this->group_table.'.group_id = '.$this->group_id.' and name = \''.$_POST['newCategory'].'\'',$this->database);
			    	if(!$category_id)
			    	{
			    		execute_query('INSERT INTO '.$this->category_table.' set name = \''.$_POST['newCategory'].'\'',$this->database);
			    		$category_id = mysql_insert_id();
			    		// todo - need to add logic here to insert multiple times for each group this category is assigned to
			    		execute_query('INSERT INTO '.$this->group_table.' set category_id = '.$category_id.', group_id = '.$this->group_id, $this->database);
			    	}
			    }
			    
			    if(!file_exists('pictures/' . $category_id))
					{
						mkdir('pictures/' . $category_id);
					}
					
			    $write_file = 'pictures/'.$category_id . '/' . $filename;
			
			    if ($g_overwrite == 0)
			    {
			        if (file_exists($write_file))
			        {
			            $this->echoError("File $filename exists, will not overwrite!");
			            return 0;
			        }
			    }
					
			
			    if(!move_uploaded_file($values['tmp_name'], $write_file))
			    {
			        $this->echoError("Could not upload file: $filename");
			    }
			    else
			    {
			    	execute_query('INSERT INTO '.$this->picture_table.' SET category_id = '.$category_id.', filename = \''.$filename.'\'',$this->database);
			    	$size = filesize($write_file);
	//		      $time_took=time() - $_SERVER['REQUEST_TIME'];
			    echo '<center>'.
						    '<hr noshade size=1 width="90%">'.
						        '<table border=0 bgcolor="#c0c0c0" cellpadding=0 cellspacing=0>'.
						'        <tr>'.
						'            <td>'.
						'                <table border=0 width="100%" cellpadding=10 cellspacing=2>'.
						'                    <tr align="center">'.
						'                        <td bgcolor="#000099" width="100%">'.
						'                        <font color="#ffffff">'.
						'                        File '.
						'                        <font color="#00ffff"><b>'.$filename.'</b></font> of size '.
						'                        <font color="#00ffff"><b>'.$size.'</b></font> bytes is '.
						'                        uploaded successfully!' . // in '.$time_took.' seconds!'.
						'                        </font>'.
						'                        </td>'.
						'                    </tr>'.
						'                </table>'.
						'            </td>'.
						'        </tr>'.
						'        </table>'.
						'	<img style="border: 0px;" src="resize.php?height=200&width=200&picture='.$write_file.'">'.
						'</center>';
			  }
			}
		}
	}
	
	private function getCaption($picture_id)
	{
		return do_query('select caption from '.$this->picture_table.' where id = '.$picture_id,$this->database);
	}
	
	private function getCategoryCaption($category_id)
	{
		return do_query('select caption from '.$this->category_table.' where id = '.$category_id, $this->database);
	}
	
	public function getCategories()
	{
		return array_field(fetch_array_data('select category_id from '.$this->group_table.' where group_id = '.$this->group_id, $this->database),'category_id');
	}
	
	public function getSlideShow($category_id=0,$href='', $interval=15,$size=500)
	{
		if($category_id and $this->can_view($category_id))
		{
			$categories = Array($category_id);
		}
		else
		{
			$categories = $this->getCategories();
		}

		$pictures = Array();
		foreach ($categories as $category)
		{
			$pictures = array_merge($pictures,$this->getPictures($category));
		}
		
		foreach ($pictures as $picture)
		{
			$filename[] = $this->getResizeUrl($picture['filename'], $size, $size, false);
			$caption[] = htmlspecialchars($this->getCaption($picture['id']));
		}

		srand();
		$index = rand(0,count($pictures)-1);
		if($href)
		{
			$rvalue .= '<a href="'.$href.'">';
		}
		$rvalue .= '<img style="border: 0px; background-color: black;" name="thumbImage" src="'.$this->getResizeUrl($pictures[$index]['filename'], $size, $size).'" />';
		if($href)
		{
			$rvalue .= '</a>';
		}
		$rvalue .= '<center><div id="caption" >'.$caption[$index].'</div></center>';
		
		$rvalue .= "<script type=\"text/javascript\">\n";
		$rvalue .= "setTimeout(\"writeImage();\",".($interval*1000).");\n";
		$rvalue .= "function writeImage()\n";
		$rvalue .= "{\n";
		$rvalue .= "	var imageInterval = ".$interval."*1000;\n";
		$rvalue .= "	imageArray = new Array('".join("','",$filename)."');\n";
		$rvalue .= "	captionArray = new Array('".join("','",$caption)."');\n";
		$rvalue .= "	imageCt = imageArray.length;\n";
		$rvalue .= "	randNum = Math.floor((Math.random()*imageCt));\n";
		$rvalue .= "	document.thumbImage.src = imageArray[randNum];\n";
		$rvalue .= "	document.getElementById('caption').innerHTML = captionArray[randNum];\n";
		$rvalue .= "	setTimeout(\"writeImage();\",imageInterval);\n";
		$rvalue .= "}\n";
		$rvalue .= "</script>\n";
		return $rvalue;
	}
	
	##------
	# echoError() - echo error message
	##------
	private function echoError($em)
	{
	    echo '<center><hr size=1 width="80%">        <table border=0 bgcolor="#000000" cellpadding=0 cellspacing=0>        <tr>            <td>         <table border=0 width="100%" cellpadding=5 cellspacing=1>                    <tr>                        <td bgcolor="#000000" width="100%">                                             <font color="#ff0000"><b>Error -</b>'.$em.'</font></td>                    </tr>                </table>            </td>        </tr>        </table></center>';
	}

}

?>