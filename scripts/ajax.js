function getAjaxObject()
{
	try
  {
  	// Firefox, Opera 8.0+, Safari
  	return new XMLHttpRequest();
  }
	catch (e)
  {
  	// Internet Explorer
  	try
    {
    	return new ActiveXObject("Msxml2.XMLHTTP");
    }
  	catch (e)
    {
    	try
      {
      	return new ActiveXObject("Microsoft.XMLHTTP");
      }
    	catch (e)
      {
      	alert("Your browser does not support AJAX!");
      	return false;
      }
    }
  }
}