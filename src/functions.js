function toggleSearch(cookie,id)
{
	var obj = document.getElementById(id);
	var oldStatus = obj.style.display;
	obj.style.display = oldStatus == 'none' ? 'block' : 'none';
	setCookie(cookie,oldStatus == 'none' ? "1" : "0",3600 * 24 * 30);
}

var fetchedCodes = new Array();
function toggleCode(url,type,id)
{
	var area = document.getElementById(type + '_area_' + id);
	if(area.style.display == 'block')
		setImgArea(type,id,false);
	else
	{
		if(fetchedCodes[id])
			setImgArea(type,id,true);
		else
		{
			url = url.replace(/__ID__/,id);
			myAjax.sendGetRequest(url,function(text) {
				document.getElementById(type + '_area_' + id).innerHTML = text;
				setImgArea(type,id,true);
				fetchedCodes[id] = true;
			});
		}
	}
}

function setImgArea(type,id,open)
{
	var area = document.getElementById(type + '_area_' + id);
	area.style.display = open ? 'block' : 'none';
	document.getElementById(type + '_img_' + id).src = 'images/cross' + (open ? 'open' : 'closed') + '.gif';
}