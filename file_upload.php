<?php

require_once 'include/scout_globals.php';
require_once 'include/picture_functions.php';

$pt = new ScoutTemplate();
$pt->setPageTitle( SITE_TITLE );
$pt->addStyleSheet('css/basic.css');
$pt->writeBanner();
$pt->writeMenu();

echo "<script type=\"text/javascript\" src=\"scripts/multifile.js\"></script>\n";

if(!$_SESSION['USER_ID'])
{
	echo 'Error: You must be logged in to see this page.';
	exit;
}

$group_id = do_query('select group_id from user, user_group where user.id = user_group.user_id and user.id = '.$_SESSION['USER_ID'],'scouts');

if($_REQUEST['category_id'] && ctype_digit((string)$_REQUEST['category_id']))
{
	$_SESSION['category_id'] = $_REQUEST['category_id'];
}
// make sure the category id is valid.
if(!$_SESSION['category_id'] || !do_query('select id from picture_categories where id = '.$_SESSION['category_id'].' and group_id = '.$group_id,'scouts'))
{
	$_SESSION['category_id'] = do_query('select id from picture_categories where group_id = '.$group_id.' order by id desc','scouts');
}

# overwrite the existing file or not. Default is to overwrite
# change the value to 0 if you do not want to overwrite an existing file.
$g_overwrite=0;

# if you want to restrict upload to files with certain extentions, change
# the value of $g_restrict_by_ext=1 ad ALSO modify the $g_allowed_ext if you
# wat to add other allowable extensions.
$g_restrict_by_ext=1;
$g_allowed_ext= Array("jpg","JPG");


#-------------- globals---------- STARTS ------------------
$g_debug=0;


#-------------- globals----------  ENDS  ------------------

// get a list of scout_groups and default to the one this user is associated with.
// He should also only be able to edit his own group, but able to view the entire troop.

if ($_POST['Upload'])
{
    doWork($group_id);
}
else
{
    echoForm($_SESSION['category_id'] ? $_SESSION['category_id'] : 0, $group_id);
}

echo '<a href="pictures.php?">Back to Gallery</a>';

exit;

##-----
# echoForm($category) - echo the HTML form
##-----
function echoForm($category_id, $group_id)
{

//    echo "<center>\n";
		echo "<br /><br />\n";
    
		echo '<form method="post" action="file_upload.php" enctype="multipart/form-data" onsubmit="return ValidateAllFields(this)">';

		echo "<table border=0 bgcolor=\"black\" cellpadding=5 cellspacing=0>\n";
    echo "<tr><td colspan=\"2\"><center><b>Upload Pictures</b>\n";
		echo "<hr noshade size=1>\n";
		echo "</td></tr>\n";
    #------------- Existing Category
    echo "<tr>\n";
    echo "<td align=\"right\">\n";
    echo "Existing Category\n";
    echo "</td>\n";
    
    echo "<td>\n";

    $categories = fetch_array_data('select id as value, name from picture_categories where group_id = '.$group_id,'scouts');
    $categories[] = Array('name' => '&lt; New Category &gt;', 'value' => 0);
    $onChangeHandler = 'if(this.value != 0) {document.getElementById(\'new_category_row\').style.display = \'none\'; } else {document.getElementById(\'new_category_row\').style.display = \'\'; }';
    echo ShowSelectList('category_id',$category_id,$categories, $onChangeHandler);
    echo "</td>\n";
    echo "</tr>\n";

    #------------- New Category
    echo "<tr id=\"new_category_row\" " . ($category_id ? "style=\"display: none;\"" : '' ) . " >\n";
    echo "<td align=\"right\">\n";
    echo " (or) New Category\n";
    echo "</td>\n";
    
    echo "<td>\n";

    echo '<input name="newCategory" type="text" size="20" />';
    echo "</td>\n";
    echo "</tr>\n";

    #------------- upload
    echo "<tr>\n";
    echo "<td align=\"right\">\n";
    echo "Pictures (limit 10):\n";
    echo "</td>\n";
    
    echo "<td>\n";
		echo '<input id="first_file_element" type="file" />';
    echo "</td>\n";
    echo "</tr>\n";
    
    echo "<tr>\n";
    echo "<td align=\"right\" colspan=\"2\">\n";
    echo "<div id=\"files_list\"></div>\n";
    echo "</td></tr>\n";
    
    echo "<script type=\"text/javascript\">\n";
    echo "var multi_selector = new MultiSelector( document.getElementById( 'files_list' ), 10 );\n";
    echo "multi_selector.addElement( document.getElementById( 'first_file_element' ) );\n";
    echo "</script>\n";



    #------------- submit
    echo "<tr>\n";
    echo "<td colspan=2 align=\"center\">\n";
    echo "<hr noshade size=1>\n";

		echo '<input type="submit" name="Upload" value="Upload" />';
    echo "</td>\n";
    echo "</tr>\n";

		echo '</form>';

    echo "</table>\n";
    echo '<script type="text/javascript">'."\n";
    echo 'function ValidateAllFields(form)'."\n";
    echo '{'."\n";
    echo '	if(form.category_id.value == 0 && !form.newCategory.value)'."\n";
    echo '	{'."\n";
    echo '		alert(\'You must select an existing category or enter a new one!\');'."\n";
    echo '		return false;'."\n";
    echo '}'."\n";
    echo 'return true;'."\n";
    echo '}'."\n";
    echo '</script>'."\n";
//    echo "</center>\n";
}



##-------
# doWork() - upload file 
##-------
function doWork($group_id)
{
//		echo '<pre>';
//    print_r($_FILES);
//    print_r($_POST);
//    print_r($_SESSION);
//    echo '</pre>';
    ##################
    $em='';
    ##################

		
    #  check if the ecessary fields are empty or not
    if(!$_POST['category_id'] and !$_POST['newCategory'])
    {
    	$em .= "You must specify the Category!<br>";
    }
    foreach ($_FILES as $file)
    {
	    if($file['name'])
	    {
	    	$file_exists = true;
	    }
	  }
	  if(!$file_exists)
	  {
	  	$em .= "You must select a file to upload!<br />";
	  }

    echoForm($_SESSION['category_id'] ? $_SESSION['category_id'] : 0, $group_id);
    if ($em)
    {
        echoError($em);
        return 0;
    }

    if (!validateUser())
    {
        echoError("Will not upload! You are not authorized to upload pictures");
        return 0;
    }

    # if you want to restrict upload to files with certain extention
    if ($g_restrict_by_ext == 1)
    {
        $file=$_FILES['upload_file']['name'];
        $ta=preg_split('/\./',$file);
        $sz=count($ta);
        if ($sz > 1)
        {
            $ext=$ta[$sz-1];
            if (!in_array($ext,$g_allowed_ext))
            {
                echoError("You are not allowed to upload this file");
                return 0;
            }

        }
        else
        {
            echoError("You are not allowed to upload this file");
             return 0;
        }
    }

    # now upload file
    uploadFile($group_id);

    if ($g_debug == 1)
    {
    		echo 'GET:<br />';
        echo_r($_GET);
        echo '<br /><br />';
        echo 'POST:<br />';
        echo_r($_POST);
    }
}

##------
# echoError() - echo error message
##------
function echoError($em)
{
    echo '<center><hr size=1 width="80%">        <table border=0 bgcolor="#000000" cellpadding=0 cellspacing=0>        <tr>            <td>         <table border=0 width="100%" cellpadding=5 cellspacing=1>                    <tr>                        <td bgcolor="#000000" width="100%">                                             <font color="#ff0000"><b>Error -</b>'.$em.'</font></td>                    </tr>                </table>            </td>        </tr>        </table></center>';
}

##--
# validate login name
# returns 1, if validated successfully
#         0 if  validation fails due to password or non existence of login 
#           name in text database
##--
function validateUser()
{
    if(isUser('scoutmaster'))
    {
			return 1;
    }
    return 0;
}
    

##--------
# uploadFile()
##--------
function uploadFile($group_id)
{
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
		    
		    if(!file_exists('pictures/' . $group_id))
				{
					mkdir('pictures/' . $group_id);
				}
				
		    $write_file = 'pictures/'.$group_id . '/' . $filename;
		    
		    if($_POST['category_id'])
		    {
		    	$category_id = $_POST['category_id'];
		    }
		    else
		    {
		    	$category_id = do_query('select id from picture_categories where group_id = '.$group_id.' and name = \''.$_POST['newCategory'].'\'','scouts');
		    	if(!$category_id)
		    	{
		    		execute_query('INSERT INTO picture_categories set group_id = '.$group_id.', name = \''.$_POST['newCategory'].'\'','scouts');
		    		$category_id = mysql_insert_id();
		    	}
		    }
		
		    echo_debug("Filename=$filename");
		    echo_debug("Writefile= $write_file");
		
		    if ($g_overwrite == 0)
		    {
		        if (file_exists($write_file))
		        {
		            echoError("File $filename exists, will not overwrite!");
		            return 0;
		        }
		    }
				
		
		    if(!move_uploaded_file($values['tmp_name'], $write_file))
		    {
		        echoError("Could not upload file: $filename");
		        return 0;
		    }
		    else
		    {
		    	execute_query('INSERT INTO pictures SET category_id = '.$category_id.', filename = \''.$filename.'\'','scouts');
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
					'	<img src="resize.php?height=200&width=200&picture='.$write_file.'">'.
					'</center>';
		  }
		}
	}
}


function echo_debug($msg)
{
	global $g_debug;
    if ($g_debug)
    {
        echo "<code>(debug) $msg</code><br>\n";
    }
}

?>