
var print_object_indent = 4;

function get_spaces(spaces){
	var r = '';
	//alert('depth='+depth);
	for (var i = 0; i < spaces; i++)
		r += ' ';
	return r;
}

function print_object(obj, depth)
{
	var rtext = '';
	if (depth == null) depth = 0;
	if ('undefined' == typeof obj){
		rtext += 'undefined';
	}
	else if (obj instanceof Array){
		var indent = get_spaces(depth*print_object_indent*2);
		var half_indent = indent + get_spaces(print_object_indent);
		rtext += "Array\n";
		rtext += indent + "(\n";
		for (var i = 0; i < obj.length; i++){
			rtext += half_indent + '['+i+'] => '+print_object(obj[i],depth+1);
		}
		rtext += indent + ")\n";
	}
	else if ('object' != typeof obj && 'function' == typeof obj.toString){
		if ('string' == typeof obj)
			rtext += '"'+obj.toString()+"\"\n";
		else
			rtext += obj.toString()+"\n";
	}
	else if (obj instanceof Object){
		var indent = get_spaces(depth*print_object_indent*2);
		var half_indent = indent + get_spaces(print_object_indent);
		rtext += "Object\n";
		rtext += indent + "{\n";
		for (var i in obj){
			rtext += half_indent + '.'+i+' => '+print_object(obj[i],depth+1);
		}
		rtext += indent + "}\n";
	}
	else {
		rtext += obj+"\n";
	}
	return rtext;
}
	