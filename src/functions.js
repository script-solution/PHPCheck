function toggleSearch(cookie,id)
{
	var obj = document.getElementById(id);
	var oldStatus = obj.style.display;
	obj.style.display = oldStatus == 'none' ? 'block' : 'none';
	setCookie(cookie,oldStatus == 'none' ? "1" : "0",3600 * 24 * 30);
}