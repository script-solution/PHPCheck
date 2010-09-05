function toggleSearch(cookie,id)
{
	var obj = document.getElementById(id);
	var oldStatus = obj.style.display;
	obj.style.display = oldStatus == 'none' ? 'block' : 'none';
	setCookie(cookie,oldStatus == 'none' ? "1" : "0",3600 * 24 * 30);
}

var checkintv = 0;
function startJobs(jobctrlURL,jobstateURL,file_count,files_per_job,check_interval)
{
	myAjax.sendPostRequest(jobctrlURL,'',function(text) {
		if(text != '')
			document.getElementById('status_msg').innerHTML = '<span class="pc_msg_error">' + text + '</span>';
		else
		{
			document.getElementById('finish_area').style.display = 'block';
			document.getElementById('status_img').style.width = '100%';
		}
		window.clearInterval(checkintv);
	});

	checkJobStatus = function()
	{
		var ajax = new FWS_Ajax();
		ajax.setMimeType('text/plain; utf-8');
		ajax.sendPostRequest(jobstateURL,'',function(text) {
			var statusmsg = document.getElementById('status_msg');
			var statusimg = document.getElementById('status_img');
			var jobs_done = parseInt(text.substr(0,text.indexOf(';')));
			var errors = text.substring(text.indexOf(';') + 1);
			var filesdone = Math.min(file_count,jobs_done * files_per_job);
			statusmsg.innerHTML = filesdone + ' of ' + file_count + ' files processed' + errors;
			statusimg.style.width = (100 * (filesdone / file_count)) + '%';
		});
	};
	
	checkintv = window.setInterval('checkJobStatus()',check_interval);
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