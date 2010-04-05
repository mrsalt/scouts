/*
functions for moving the div around:
  positionDiv
  grabDiv
  moveDiv
  dropDiv
  hideDiv
    
utility functions:
  userHasRequirement(_user_id, _req_id)
  getPassOffCount(_req_id)
  getChangedCount()
  getUserCheckCount()
  
action functions:
  toggleRequirement(req_id, li_element)
  applyUpdates()
    getUpdateURL()
    
handler functions:
  ClickReq(req_id, li_element)
    toggleRequirement
  clickSignOff()
  clickUserCheckbox(uid)
  
html rendering functions:
  setDivContents()
    getUserChecklist(show_disabled)
  updateReqView  
*/

var DL_bNS4=(document.layers);
var DL_bNS6 = (navigator.vendor == ("Netscape6") || navigator.product == ("Gecko"));
var DL_bDOM=(document.getElementById);
var DL_bIE=(document.all);
var DL_bIE4=(DL_bIE && !DL_bDOM);
var DL_bMac = (navigator.appVersion.indexOf("Mac") != -1);
var DL_bIEMac=(DL_bIE && DL_bMac);
var DL_bIE4Mac=(DL_bIE4 && DL_bMac);
var DL_bNS =(DL_bNS4 || DL_bNS6);

var oldX; var oldY;
eDiv = document.getElementById('editDiv');

function positionDiv(x,y){
  eDiv.style.left = x + 'px';
  eDiv.style.top = y + 'px';
}

function grabDiv(e){
  oldX = (DL_bNS ? e.pageX : event.clientX + document.body.scrollLeft);
  oldY = (DL_bNS ? e.pageY : event.clientY + document.body.scrollTop);
  document.onmousemove=moveDiv;
  document.onmouseup=dropDiv;
  return false;
}

function moveDiv(e){
  cX = (DL_bNS ? e.pageX : event.clientX + document.body.scrollLeft);
  cY = (DL_bNS ? e.pageY : event.clientY + document.body.scrollTop);
  dX = cX - oldX; dY = cY - oldY;
  oldX = cX; oldY = cY;
  positionDiv(parseInt(eDiv.style.left) + dX, parseInt(eDiv.style.top) + dY);
  return false;
}

function dropDiv(e){
  document.onmousemove=null;
  document.onmouseup=null;
  return false;
}

function hideDiv(){
	eDiv.style.visibility='hidden';	
}

//function dropDiv()
//{
//  eDiv.style.visibility='hidden';
//}

//function ShowLess(li_item, award_title, number, req_id){
//  setDivContents(award_title, number, li_item.innerHTML, req_id);
//  positionDiv(200,300);
//  eDiv.style.visibility='visible';
//}

//--------------------------------------

var reqs_changed = new Array();
var modeIsPassOff = true;
var inputMode = 'normal'; // normal, quick_fill
//var last_signed_by = 0;
var currentFocus = '';
var currentSubFocus = '';
var currentSignedBy = '';

function userHasRequirement(_user_id, _req_id)
{
   var uid = 0;
   var rid = 0;

   for (uid in user_reqs)
   {
    	for (rid in user_reqs[uid])
    	{
    		if (_user_id == uid && _req_id == rid)
    		{
    			if (user_reqs[uid][rid]['signed_by'] != '0')
    				return true;
    			else
    				return false;
    		}
    	}	
    }
    return false;
}

function getPassOffCount(_req_id)
{
    var count = 0;
    var uid = 0;
    var rid = 0;

	for (uid in user_reqs)
    {
    	for (rid in user_reqs[uid])
    	{
    		if (_req_id == rid && user_reqs[uid][rid]['signed_by'] != '0')
    			count++;
    	}	
    }
    return count;
}

function getChangedCount()
{
	var count = 0;
	var req_id = 0;
	for (req_id in reqs_changed)
	{
		if (reqs_changed[req_id])
			count++;
	}
	return count;
}

//function getSignedByCount()
//{
//	var req_id = 0;
//	var uid = 0;
//	var signed_by_users = new Array();
//
//	for (req_id in reqs_changed)
//	{
//		if (reqs_changed[req_id])
//		{
//			for (uid in user_info)
//			{
//				//if ((e = document.getElementById('user_' + uid)) && e.checked)
//				if (user_info[uid]['checked'] && userHasRequirement(uid, req_id))
//				{
//					signed_by_users[ user_reqs[uid][req_id]['signed_by'] ] = 1;
//					last_signed_by = user_reqs[uid][req_id]['signed_by'];
//				}
//			}
//		}
//	}
//	return signed_by_users.length;
//}

function getReqSavedStateText(req_id)
{
	pass_off_count = getPassOffCount(req_id);
	if (pass_off_count == 0)
		return '&nbsp;';
	else if (user_count == 1)  // pass off count must be 1 in this case
		return 'x';
	else if (pass_off_count == user_count)
		return 'All';
	else
		return '('+pass_off_count+')';
}

function toggleRequirement(req_id, li_element)
{
  if (reqs_changed[req_id])
  	reqs_changed[req_id] = false;
  else
    reqs_changed[req_id] = true;
  
  if (reqs_changed[req_id])
  {
  	if (modeIsPassOff)
  	{
      if (user_count == 1)
        new_state = '<u>&nbsp;x&nbsp;<\/u> ';
      else
        new_state = '<u>&nbsp;All&nbsp;<\/u> ';
      li_element.style.background = '#CFD5D8';
      //li_element.style.color = 'white';
      li_element.style.fontWeight = 'bold';
	}
	else
	{
	  new_state = '<u>&nbsp;&nbsp;&nbsp;<\/u> ';
	  li_element.style.background = '#CFD5D8';
      li_element.style.color = 'red';
      li_element.style.fontWeight = 'bold';
    }
  }
  else  // restore to saved state
  {
  	new_state = '<u>&nbsp;'+getReqSavedStateText(req_id)+'&nbsp;<\/u> ';
  	li_element.style.background = 'white';
    li_element.style.color = 'black';
    li_element.style.fontWeight = 'normal';
  }
	
  li_element.innerHTML = li_element.innerHTML.replace(/<u>.+<\/u>\s+/i,new_state);
}

function ClickReq(req_id, li_element)
{
	if (requirements[req_id]['req_type'] == 'Merit Badge')
	{
		alert('To sign off merit badges requirements, sign off Merit Badges individually through the menu at the left and the requirements will automatically be passed off in this section.');
		return;	
	}
	
	if (inputMode != 'normal')
		return;

	if (modeIsPassOff)
	{
		if (getPassOffCount(req_id) == user_count)
		{
			if (getChangedCount() == 0)
			{
//				if (confirm('Are you sure you want to erase the sign-off for this requirement?'))
//				{
//					document.getElementById('button_sign_off_reqs').value = 'Delete Sign-Off';
//					modeIsPassOff = false;
//				}
//				else
//					return;
				// Actually, the user will have to confirm later on that they are deleting a sign off... don't make
				// them confirm it twice.
				document.getElementById('button_sign_off_reqs').value = 'Delete Sign-Off';
				modeIsPassOff = false;
				document.getElementById('button_mark_all').disabled = true;
			}
			else
				return;  // All users have passed this req off... nothing to do.
		}
	}
	else
	{
		if (getPassOffCount(req_id) == 0)
			return;	
	}
	
	toggleRequirement(req_id, li_element);
	setDivContents();
	
	if (getChangedCount() == 0)
	{
		document.getElementById('button_sign_off_reqs').value = 'Sign Off Reqs';
		modeIsPassOff = true;
		document.getElementById('button_mark_all').disabled = false;
		hideDiv();
	}
	//document.getElementById('button_sign_off_reqs').disabled = (getChangedCount() == 0);
	
	//alert('user_count='+user_count+', user 6 has this req = '+( userHasRequirement(6,req_id) ? 'true' : 'false'));
	
    //requirements[req_id]['number'] may not actually be what the user sees...
    // I should probably build a separate array for that.
    //p = '';
    
    //setDivContents(award_data['title'], requirements[req_id]['number'], p, req_id);  //requirements[req_id]['description']
    //positionDiv(200,300);
    //eDiv.style.visibility='visible';
}

function isParentRequirement(parent_req_id)
{
	for (is_p_req_id in requirements)
	{
		if (requirements[is_p_req_id]['parent_id'] == parent_req_id)
		{
			return true;
		}
	}
	return false;
}

var quick_fill_req_id = '';

function findCurrentReq(start_after)
{
	var req_id = 0;
	var start_after_found = false;
	
	for (req_id in requirements)
	{
		if (start_after && !start_after_found)
		{
			start_after_found = (req_id == start_after)
			continue;
		}
		//alert('checking req_id '+req_id+' (req num '+requirements[req_id]['user_number']+'), req_changed='+(reqs_changed[req_id] ? 'true' : 'false')+', merit badge='+(requirements[req_id]['req_type'] == 'Merit Badge' ? 'true' : 'false')+', parent req='+(isParentRequirement(req_id) ? 'true' : 'false')+', pass off count='+getPassOffCount(req_id)+', user_count='+ user_count);
		if (!reqs_changed[req_id] && (requirements[req_id]['req_type'] != 'Merit Badge') && !isParentRequirement(req_id) && getPassOffCount(req_id) < user_count)
		{
			//alert('going with req id '+req_id+' (req num '+requirements[req_id]['user_number']+')');
			return req_id;
		}
	}
	return '';
}

function getCurrentQuickFillRequirement()
{
	if (!quick_fill_req_id)
	{
		//alert('quick fill is not set');
		quick_fill_req_id = findCurrentReq();
		toggleRequirement(quick_fill_req_id, document.getElementById('li_'+quick_fill_req_id));
		return quick_fill_req_id;
	}
	else
		return quick_fill_req_id;
}

function skipToNext(toggle)
{
	if (quick_fill_req_id)
	{
		// turn the current quick fill off
		if (toggle)
			toggleRequirement(quick_fill_req_id, document.getElementById('li_'+quick_fill_req_id));
	
		quick_fill_req_id = findCurrentReq(quick_fill_req_id);
		
		if (quick_fill_req_id)
		{
			toggleRequirement(quick_fill_req_id, document.getElementById('li_'+quick_fill_req_id));
			setDivContents();
		}
	}
}

function clickQuickFill()
{
	if (inputMode != 'quick_fill')
	{
		inputMode = 'quick_fill';
		currentFocus = (allowProxySigning ? 'signed_by' : 'sign_date_td');
		currentSubFocus = 'sign_date_month';
		quick_fill_req_id = '';
		getCurrentQuickFillRequirement();
		setDivContents();
	}
	positionDiv(200,300);
    eDiv.style.visibility='visible';
}

var mark_all_value = 'Mark All';
function clickMarkAll()
{
	if (inputMode != 'normal')
		return;

	var req_id = 0;
	// mark all should check all the requirements that are not checked, and 
	// clear all the ones that are not passed off if clicked again... it 
	// shouldn't do anything to ones that are already signed off for all users
	for (req_id in requirements)
	{
		if (!isParentRequirement(req_id) && getPassOffCount(req_id) < user_count && requirements[req_id]['req_type'] != 'Merit Badge')
		{
			//ClickReq(req_id, document.getElementById('li_'+req_id));
			if (mark_all_value == 'Mark All')
			{
				if (!reqs_changed[req_id])
					toggleRequirement(req_id, document.getElementById('li_'+req_id));
			}
			else
			{
				if (reqs_changed[req_id])
					toggleRequirement(req_id, document.getElementById('li_'+req_id));
			}
		}
	}

	setDivContents();
	
	mark_all_value = (mark_all_value == 'Mark All' ? 'Clear All' : 'Mark All');
	document.getElementById('button_mark_all').value = mark_all_value;
}

function clickSignOff()
{
	if (inputMode != 'normal')
	{
		hideDiv();
		inputMode = 'normal';
		currentFocus = (allowProxySigning ? 'signed_by' : 'sign_date_td');
		currentSubFocus = 'sign_date_month';
		setDivContents();
	}
	if (getChangedCount() == 0)
	{
		alert('To sign off requirements, first click the requirements below that you wish to sign off.');
		return;
	}
	positionDiv(200,300);
	eDiv.style.visibility='visible';
}

function onKeyUpHandler(e)
{
	var evt=(e)?e:(window.event)?window.event:null;
	if(evt){
		var key=(evt.charCode)?evt.charCode: ((evt.keyCode)?evt.keyCode:((evt.which)?evt.which:0));
		//var e_keyCode = (DL_bNS ? e.keyCode : event.keyCode);
		//var key=(evt.charCode)?evt.charCode: ((evt.keyCode)?evt.keyCode:((evt.which)?evt.which:0));
		var key_id = parseInt(key);
		//if (inputMode == 'quick_fill')
		//{
		//currentFocus -- 'signed_by' : 'sign_date_td'
		if (currentFocus == 'signed_by')
		{
			if (key_id >= 49 && key_id <= 57 || // numbers 1-9 (top of keyboard)
			    key_id >= 97 && key_id <= 105)  // numbers 1-9 (with numlock keypad)
			{
				var user_id = 0;
				var sm_count = 0;
				var sm_number = 0;
				if (key_id >= 49 && key_id <= 57)
					sm_number = key_id - 48;
				else //if (key_id >= 97 && key_id <= 105)
					sm_number = key_id - 96;
				for (user_id in scoutmasters)
				{
					sm_count++;
					if (sm_count == sm_number)
					{
						onClickSignedBy(user_id);
						return;
					}
				}
			}
			else if (key_id == 13 || key_id == 9) // Enter or Tab
			{
				onClickSignedBy(currentSignedBy); // don't change who's selected, just move to next field
			}
		}
		else if (currentFocus == 'sign_date_td')
		{
			if (currentSubFocus)
			{
				//if (key_id==39)
				//alert('currentSubFocus='+currentSubFocus);
				if (key_id == 13 || key_id == 39) // Enter key, right arrow key takes you to next field
				{
					if (currentSubFocus == 'sign_date_month') setFocus('sign_date_td', 'sign_date_day');
					else if (currentSubFocus == 'sign_date_day') setFocus('sign_date_td', 'sign_date_year');
					else if (currentSubFocus == 'sign_date_year') setFocus('sign_button', '');
					return;
				}
				else if (key_id == 37) // Left key
				{
					if (currentSubFocus == 'sign_date_day') setFocus('sign_date_td', 'sign_date_month');
					else if (currentSubFocus == 'sign_date_year') setFocus('sign_date_td', 'sign_date_day');
					return;
				}
				var dval = parseInt(document.getElementById(currentSubFocus).value);
				
				if (currentSubFocus == 'sign_date_month' && dval > 1)
					setFocus('sign_date_td', 'sign_date_day');
				else if (currentSubFocus == 'sign_date_day' && dval != 1 && dval != 2 && dval != 3)
					setFocus('sign_date_td', 'sign_date_year');
				else if (currentSubFocus == 'sign_date_year' && dval > 1900)
					setFocus('sign_button', '');
			}
		}
		//}
	}
// event.keyCode
}

function getUserChecklist(show_disabled)
{
	var html = '';

	if (user_count < 6)
	{
		for (uid in user_info)
		{
			html += '<input type="checkbox" onClick="clickUserCheckbox(' + uid + ');" id="user_' + uid + '" '+(user_info[uid]['checked'] ? 'checked="checked" ' : '') + (show_disabled ? ' disabled="disabled" ' : '')+'> ' + user_info[uid]['name'] + '<br>';
		}
	}
	else
	{
		html = '<table style="color: black">';
		var u_count = 0;
		for (uid in user_info)
		{
			if (u_count % 2 == 0)
				html += '<tr>';
			html += '<td><input type="checkbox" onClick="clickUserCheckbox(' + uid + ');" id="user_' + uid + '" '+(user_info[uid]['checked'] ? 'checked="checked" ' : '') + (show_disabled ? ' disabled="disabled" ' : '')+'> ' + user_info[uid]['name'] + '</td>';
			u_count++;
			if (u_count % 2 == 0)
				html += '</tr>';
		}
		if (u_count % 2 != 0)
				html += '<td></td></tr>';
		html += '</table>';
	}	
	return html;
}

function clickUserCheckbox(uid)
{
	if ((e = document.getElementById('user_' + uid)) && e.checked)
		user_info[uid]['checked'] = true;
	else
	{
		user_info[uid]['checked'] = false;
	}
	setDivContents();
}

function getUserCheckCount()
{
	count = 0;
	for (uid in user_info)
	{
		//if ((e = document.getElementById('user_' + uid)) && e.checked)
		if (user_info[uid]['checked'])
			count++;
	}
	return count;
}

function uncheckAllUsers()
{
	var uid = 0;
	for (uid in user_info)
	{
		var e = document.getElementById('user_' + uid);
		if (e && e.checked)
		{
			e.checked = false;
			user_info[uid]['checked'] = false;
		}
	}
	setDivContents();
}

function getCurrentDateValue()
{
	//return document.getElementById('sign_date').value;
	return document.getElementById('sign_date_month').value + '/' +
			+ document.getElementById('sign_date_day').value + '/' +
			+ document.getElementById('sign_date_year').value;
}

function getUpdateURL()
{
	var url = 'requirements.php?action='+(modeIsPassOff ? 'sign_requirements' : 'erase_requirements')+'&reqs=';
	sep = '';
	for (req_id in reqs_changed)
	{
		if (reqs_changed[req_id])
		{
			url += sep + req_id;
			sep = ',';
		}
	}
	url += '&users=';
	sep = '';
	for (uid in user_info)
	{
		if (user_info[uid]['checked'])
		{
			url += sep + uid;
			sep = ',';
		}
	}
	if (modeIsPassOff)
	{
		url += '&sign_date=' + getCurrentDateValue();
	}
	if (currentSignedBy)
		url += '&signed_by=' + currentSignedBy;
	else
		url += '&signed_by=' + active_user_id;
	//alert(url);
	return url;
}

function updateReqView(signed_by, signed_by_name)
{
	// basically this function should make the page look like it would
	// if we submitted and the server refreshed the page for us...
	if (modeIsPassOff)
		sign_date = getCurrentDateValue();
	
	for (req_id in reqs_changed)
	{
		if (reqs_changed[req_id])
		{
			req_title = '';
			li_element = document.getElementById('li_' + req_id);
  			li_element.style.background = 'white';
	  		li_element.style.fontWeight = 'normal';
	  		li_element.style.color = 'black';
	  		// update li_element titles
	  		for (uid in user_info)
			{
				if (user_info[uid]['checked'])
				{
					if (modeIsPassOff)
					{
						if(!user_reqs[uid])
						{
							user_reqs[uid] = Array();
						}
						user_reqs[uid][req_id] = {'user_id' : uid, 'req_id' : req_id, 'completed_date' : sign_date, 'signed_date' : sign_date, 'signed_by' : signed_by};
						
						if (user_count == 1)
							req_title += 'Signed by ' + signed_by_name+' on ' + sign_date;
						else	
							req_title += user_info[uid]['name'] + ' (signed by ' + signed_by_name + ' on ' + sign_date + ")\\n";
					}
					else
					{
						user_reqs[uid][req_id]['signed_date'] = '';
						user_reqs[uid][req_id]['signed_by'] = '0';
						//delete(user_reqs[uid][req_id]);
					}
				}
			}
			li_element.title = req_title;
		}
	}
	reqs_changed = new Array();
	    			
//user_reqs = {
//  6 : {
//    1 : {
//      'user_id' : '6',
//      'req_id' : '1',
//      'completed_date' : '2005-05-17',
//      'signed_date' : '2005-05-17',
//      'signed_by' : '1'},

}

function applyUpdates()
{
	if (!getChangedCount())
	{
		alert('Please select requirements to pass off.');
		return;
	}
	if (!getUserCheckCount())
	{
		alert('Please select one or more scouts to pass requirements off for.');
		return;
	}
	if (!modeIsPassOff)
	{
		warning_message = 'Warning!!!\n\n';
		warning_message += 'You are about to erase ';
		if (getUserCheckCount() > 1 || getChangedCount() > 1)
		{
			warning_message += 'multiple signatures.';
			warning_message += '  ('+getChangedCount()+' requirement pass off'+(getChangedCount() == 1 ? '' : 's');
			if (user_count > 1)
				warning_message += ' for '+getUserCheckCount()+' scouts';
			warning_message += ' will be deleted).';
		}
		else
			warning_message += 'a signature.';
		
		warning_message += '  Are you sure you want to proceed?';
		if (!confirm(warning_message))
			return;
	}
	
	currentFocus = '';
	
	url = getUpdateURL();
	// set the default date to whatever value we're using
	if (modeIsPassOff)
		default_date = getCurrentDateValue();
	updateReqView(active_user_id, active_user_name);
	
	if (inputMode == 'normal')
	{
		document.getElementById('button_sign_off_reqs').value = 'Sign Off Reqs';
		modeIsPassOff = true;
		document.getElementById('button_mark_all').disabled = false;
		hideDiv();
	}
	else if (inputMode == 'quick_fill')
	{
		// this should advance us to the next requirement
		skipToNext(false);
		setFocus('signed_by','');
		//setFocus('sign_date_td','sign_date_month');
		if (!quick_fill_req_id)
			hideDiv();
	}
	
	//di = new Image();
	//di.src = url;
	//alert('setting location to '+url);
	location = url;
}

function setFocus(main_focus, sub_focus)
{
	currentFocus = main_focus;
	currentSubFocus = sub_focus;
	try {
		if (main_focus == 'sign_button')
		{
			document.getElementById(main_focus).focus();
			document.getElementById(main_focus).select();		
		}
		else if (sub_focus)
		{
			document.getElementById(sub_focus).focus();
			document.getElementById(sub_focus).select();
		}
	}
	catch (e){
	}
	if (main_focus == 'signed_by')
		document.getElementById('sm_'+currentSignedBy).style.border = '1px solid red';
}

function onClickSignedBy(user_id)
{
	currentSignedBy = user_id;
	setFocus('sign_date_td','sign_date_month');
	document.getElementById('signed_by_table').innerHTML = getSignedByHTML(currentSignedBy, false);
}

function getSignedByHTML(signed_by, has_focus)
{
	var sm_count = 0;
	var td_style = '';
	var on_click = '';
	var html = 'Signed By<table border=0 cellpadding=5><tbody style="color: black;">';
	html += '<tr>';
	for (user_id in scoutmasters)
	{
		if (sm_count % 3 == 0)
			html += '</tr><tr>';
		sm_count++;
		if (user_id == signed_by)
		{
			td_style = ' style="background: white; border: 1px solid '+(has_focus ? 'red' : 'gray')+'"';
			//td_style = ' style="background: white; border: 1px solid gray"';
			on_click = ''; 
		}
		else
		{
			td_style = ' style="cursor: pointer"';
			on_click = ' onClick="onClickSignedBy('+user_id+')"';
		}
		html += '<td id="sm_'+user_id+'"'+td_style+on_click+'>'+sm_count+'. '+scoutmasters[user_id]['name']+'</td>';
	}
	html += '</tr></tbody></table>';
	return html;
}

function setDivContents()
{
	//description = description.replace(/<u>.+<\/u>\s+/i,'');
	var html = '';
	var req_id = '';
	var divWidth = 350; //320
	var descDivHeight = (inputMode == 'normal' ? 130 : 80);  //80
	var user_id = 0;

	html += '<div style="padding: 0px; background-image: url(\'images/window_bar.png\'); background-repeat: repeat-x;"><table border=0><tr><td width="'+divWidth+'px">';
	html += '<div id="el_move_div" style="width: '+divWidth+'px;">&nbsp;</div></td><td width="20px" align="right">';
	html += '<div style="padding: 0px; width: 21px; height: 21px; cursor: pointer; background-image: url(\'images/exit_button.png\');" onClick="hideDiv();" title="Close Window"></div></td></tr></table></div>';
//  html += '<div style="width: 20px; border: 1px solid black; cursor: pointer; color: white; background: red; text-align: center;" onClick="hideDiv();" title="Close Window"><b>X</b></div></td></tr></table></div>';
	html += '<div style="padding: 10px;"><table border=0 cellpadding=5><tbody style="color: black;">';
	
	html += '<tr><td style="font-size: larger;">'+award_data['title']+'<br>'+award_data['type']+'</td></tr>';
	//Requirement '+number+'

	html += '<tr><td>'+getUserChecklist(user_count == 1)+'</td></tr>';
	if (user_count > 2)
		html += '<tr><td><input type="button" value="Uncheck All" onClick="uncheckAllUsers()" '+(getUserCheckCount() == 0 ? 'disabled ':'')+'id="uncheck_all_button"></td></tr>';
	
	var button_enabled = getChangedCount() && getUserCheckCount();
	var description = '';
	if (inputMode == 'normal')
	{
		button_enabled = true;
		var sep = '';
		for (req_id in reqs_changed)
		{
			if (reqs_changed[req_id])
			{
				description += sep+'<b>'+requirements[req_id]['user_number']+'</b> '+requirements[req_id]['description'];
				sep = '<br><hr>';
			}
		}
	}
	else if (inputMode == 'quick_fill')
	{
		req_id = getCurrentQuickFillRequirement();
		description = '<b>'+requirements[req_id]['user_number']+'</b> '+requirements[req_id]['description'];
	}
	//html += '<tr><td>'+user_name+'</td></tr>';  //('+user_id+')
	
	html += '<tr><td><div style="width: '+(divWidth - 20)+'px; height: '+descDivHeight+'px; overflow: auto; border: 1px solid black; padding: 10px; background: white;">'+description+'</div></td></tr>';
//	if (modeIsPassOff && allowProxySigning)
//	{
//		var signed_by_count = getSignedByCount();
//		if (signed_by_count == 1)
//		{
//			html += '<td style="color: black;" width="100%">';
//			html += 'Signed By <select size="1" id="signed_by">';
//			for (user_id in scoutmasters)
//			{
//				html += '<option value="'+user_id+'" '+(user_id == last_signed_by ? ' selected':'')+'>'+scoutmasters[user_id]['name']+'</option>';	
//			}
//			html += '</select>';
//			html += '</td>';
//		}
//	}
	if (modeIsPassOff && allowProxySigning)
	{
		//currentFocus -- 'signed_by' : 'sign_date_td'	
		html += '<tr><td><span id="signed_by_table">';
		html += getSignedByHTML(currentSignedBy, (currentFocus == 'signed_by'));
		html += '</span></td></tr>';
	}
	html += '<tr><td><table border=0><tr>';
	if (modeIsPassOff)
	{
		html += '<td style="color: black;" width="100%" id="sign_date_td">';
		//if (inputMode == 'normal')
		//	html += 'Signed On <input type="text" value="'+default_date+'" size="10" id="sign_date">';
		//else if (inputMode == 'quick_fill')
		//{
			html += 'Signed On <input type="text" onClick="setFocus(\'sign_date_td\', \'sign_date_month\')" value="'+date_to_month(default_date)+'" maxlength="2" size="1" id="sign_date_month">';
			html += '/<input type="text" onClick="setFocus(\'sign_date_td\', \'sign_date_day\')" value="'+date_to_day(default_date)+'" maxlength="2" size="1" id="sign_date_day">';
			html += '/<input type="text" onClick="setFocus(\'sign_date_td\', \'sign_date_year\')" value="'+date_to_year(default_date)+'" maxlength="4" size="3" id="sign_date_year">';
		//}
		html += '</td>';
	}
	html += '<td align="right"'+(modeIsPassOff ? '' : ' colspan="2"')+'>';
	html += '<input id="sign_button" type="button" onClick="applyUpdates()" value="'+(modeIsPassOff ? 'Sign Now' : 'Erase Now')+'"'+(button_enabled ? '':' disabled')+'>';
	if (inputMode == 'quick_fill')
	{
		html += ' &nbsp; <input id="skip_button" type="button" onClick="skipToNext(true)" value="Skip >">';
	}
	html += '</td></tr></table></td></tr>';
	html += '</tbody></table></div>';
	eDiv.innerHTML = html;
	document.getElementById('el_move_div').onmousedown=grabDiv;
}

function date_to_month(date_val)
{
	var month_regex = /(\d{1,2})[\/-]\d{1,2}[\/-]\d{4}/	
	var m = month_regex.exec(date_val);
  	if (m != null)
  		return m[1];
}

function date_to_day(date_val)
{
	var day_regex =   /\d{1,2}[\/-](\d{1,2})[\/-]\d{4}/
	var m = day_regex.exec(date_val);
  	if (m != null)
  		return m[1];
}

function date_to_year(date_val)
{
	var year_regex =  /\d{1,2}[\/-]\d{1,2}[\/-](\d{4})/
	var m = year_regex.exec(date_val);
  	if (m != null)
  		return m[1];
}

function clickMakeOptional()
{
	var url = 'requirements.php?action=make_optional&reqs=';
	var mo_count = 0;
	var sep = '';
	for (req_id in reqs_changed)
	{
		if (reqs_changed[req_id])
		{
			url += sep + req_id;
			sep = ',';
			mo_count++;
		}
	}
	if (mo_count > 0)
		location = url;
}

document.onkeyup=onKeyUpHandler;
currentSignedBy = active_user_id;
