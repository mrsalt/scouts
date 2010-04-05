/*
functions for moving the div around:
  positionDiv
  grabDiv
  moveDiv
  dropDiv
  hideDiv
    
html rendering functions:
  setDivContents()
    getUserChecklist(show_disabled)
  updateReqView  
*/
document.write('<script type="text/javascript" src="scripts/CalendarPopup.js"></script>');
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


function clickEdit(id)
{
//	if (getChangedCount() == 0)
//	{
//		alert('To sign off requirements, first click the requirements below that you wish to sign off.');
//		return;
//	}
	setDivContents(events[id]);
	positionDiv(200,200);
  eDiv.style.visibility='visible';
}

function startDateChanged(y, m, d)
{
	CP_tmpReturnFunction(y, m, d);	
	document.calendar.elements['end_date'].value = document.calendar.elements['start_date'].value;
	//cal.setReturnFunction("CP_tmpReturnFunction");
	//cal.returnFunction(y, m, d);
	//alert('start date changed');
}

function updateEndDate()
{
	// makes sure that the end date is never before the start date
	var start_date = new Date(document.calendar.elements['start_date'].value);
	var end_date = new Date(document.calendar.elements['end_date'].value);
	if(start_date > end_date)
	{
		document.calendar.elements['end_date'].value = document.calendar.elements['start_date'].value;
	}
}

function setDivContents(event)
{
  var html = '';
  divWidth = 350;
  descDivHeight = 370;
  // window title bar
  html += '<div style="cursor: default; padding: 0px; spacing: 0px; background-image: url(\'images/window_bar.png\'); background-repeat: repeat-x;"><table border="0" ><tr><td width="'+divWidth+'px">';
  html += '<div id="el_move_div" style="width: '+divWidth+'px; color: #595959; font-size: 90%; font-weight: bold;">Edit Calendar Event</div></td><td style="padding: 0px;" align="right">';
  html += '<div style="padding: 0px; width: 21px; height: 21px; cursor: pointer; background-image: url(\'images/exit_button.png\');" onClick="hideDiv();" title="Close Window"></div></td></tr></table></div>';
  html += '<div style="padding: 10px;"><table border=0 cellpadding=5><tbody style="color: black;">';
	
	// window contents
	contents = '';
	contents += '<form name="calendar" action="calendar.php" method="post">';
	contents += '<input type="hidden" name="id" value="'+event['id']+'" />';
	contents += 'From <input name="start_date" type="text" size="10" value="'+event['start_date']+'" onchange="updateEndDate();" /><a id="calendar_anchor1" name="calendar_anchor1" href="#" onclick="cal.setReturnFunction(\'startDateChanged\'); cal.select(document.calendar.start_date,\'calendar_anchor1\',\'MM/dd/yyyy\'); return false;"> <img style="vertical-align: bottom;" src="images/cal.gif" width=24 border=0 /></a> to ';
	contents += '<input name="end_date" type="text" size="10" value="'+event['end_date']+'" onmouseover="updateEndDate();" /><a id="calendar_anchor2" name="calendar_anchor2" href="#" onclick="cal.setReturnFunction(\'CP_tmpReturnFunction\'); cal.select(document.calendar.end_date,\'calendar_anchor2\',\'MM/dd/yyyy\'); return false;" onmouseover="updateEndDate();" > <img style="vertical-align: bottom;" src="images/cal.gif" width=24 border=0 /></a><br />';
	contents += '<br />Activity:<br />';
	contents += '<textarea name="activity" rows="4" cols="35" onmouseover="updateEndDate();" >'+event['activity']+'</textarea><br />';
	contents += '<br />Requirements:<br />';
	contents += '<textarea name="requirements" rows="4" cols="35" onmouseover="updateEndDate();" >'+event['requirements']+'</textarea><br />';
	contents += '<input type="hidden" name="action" value="update_event" />';
	contents += '<table><tr><td valign="top">';
	contents += '<input type="submit" name="submit" value="submit" />&nbsp;&nbsp;';
	contents += '</td><td style="color: black">';
	//contents += '<input type="checkbox" name="group[0]" value="0"'+(eventAppliesToGroup(event, 0) ? ' checked':'')+'> All Scouts';
	var sep = '';
	for (var i in groups){
		contents += sep + '<input type="checkbox" name="group['+groups[i]['id']+']" value="'+groups[i]['id']+'"'+(eventAppliesToGroup(event, groups[i]['id']) ? ' checked':'')+'> '+groups[i]['group_name'];
		sep = '<br/>';
	}
	contents += '</td></tr></table>';
	
	contents += '</form>';
  html += '<tr><td><div style="width: '+(divWidth - 20)+'px; height: '+descDivHeight+'px; overflow: auto; border: 1px solid black; padding: 10px; background: white;">'+contents+'</div></td></tr>';
  html += '</tbody></table></div>';
  eDiv.innerHTML = html;
  document.getElementById('el_move_div').onmousedown=grabDiv;
}

function eventAppliesToGroup(event, group_id){
	var is_checked = false;
	for (var j = 0; j < event['group_ids'].length; j++){
		if (event['group_ids'][j] == group_id) is_checked = true;
	}
	return is_checked;
}
