(function() {
	var size = 0;
	var start = 0;
	var chknum = 1;
	var sliceSize = 1;
	var file;
	var data_name = '';
	var is_last = false;
	var sent_chnk = 0;
	var percent = 0;
	var on_upload = false;
	var f = document.getElementById('file-uploadi');
	var btn = document.getElementById('btn-uploadi');
	var div = document.getElementById('queue');

	//if (f.files.length)
	//  processFile();

	f.addEventListener('change', addFile, false);
	btn.addEventListener('click', processFile, false);

	function addFile(e) {
		console.log('addfile');
		if(on_upload == false){
			file = f.files[0];
			div.innerHTML = '<div class="uploadifive-queue-item" id="uploadifive-file_upload-file-0"><a class="close" href="#">X</a><div><span class="filename">'+file.name+'</span><span class="fileinfo"></span></div><div class="progress"><div class="progress-bar"></div></div></div>';
		}
		
	}

	function processFile(e) {	
		console.log('processFile');
		if(on_upload == false){
			file = f.files[0]; // This is your file object
			size = file.size;
			sliceSize = 10000000; // Send 10MB Chunks
			start = 0;
			chknum = 1;
			data_name = file.name;
			is_last = false;

			//console.log('Sending File of Size: ' + size);
			on_upload = true;
			send(file, 0, sliceSize);
		}
	}


	function send(file, start, end) {
		var formdata = new FormData();
		var xhr = new XMLHttpRequest();

		if (size - end < 0) { // Uses a closure on size here you could pass this as a param
			end = size;
		}
		if (end < size) {
			is_last = false;
			
		} else {
			is_last = true;
		}
		sent_chnk = sent_chnk + (end - start);
		percent = (sent_chnk * 100) / size;

		xhr.open('POST', '/classrom/upload.php', true);
		xhr.addEventListener("progress", updateProgress);
		
		xhr.addEventListener("error", function (e) {
			console.log('Err: ' + xhr.status);
			alert('Error Uploading file: ' + xhr.status);
        });
		
		xhr.addEventListener("abort", function (e) {
			console.log('User abort');
        });
		
		xhr.addEventListener("load", function(e){
			console.log(percent);
			console.log('DONE ' + xhr.response);
			div.innerHTML = '<div class="uploadifive-queue-item" id="uploadifive-file_upload-file-0"><a class="close" href="#">X</a><div><span class="filename">'+data_name+'</span><span class="fileinfo"> --> '+Math.round(percent)+'%</span></div><div class="progress"><div class="progress-bar" style="width: '+Math.round(percent)+'%"></div></div></div>';
			if(percent == 100){
				on_upload = false;
				div.innerHTML = '<div class="uploadifive-queue-item" id="uploadifive-file_upload-file-0"><a class="close" href="#">X</a><div><span class="filename">'+data_name+'</span><span class="fileinfo"> --> COMPLETE</span></div><div class="progress"><div class="progress-bar" style="width: 100%"></div></div></div>';
				alert('File has been Uploaded');
				//window.location.reload(true);
			}
			if (end < size) {
				is_last = false;
				if (xhr.readyState == XMLHttpRequest.DONE) {
					
					chknum = chknum + 1;
					send(file, start + sliceSize, start + (sliceSize * 2))
				}
			} else {
				is_last = true;
			}
		});

		var slicedPart = slice(file, start, end);

		formdata.append('filenum', chknum);
		//formdata.append('start', start);
		//formdata.append('end', end);
		formdata.append('file', slicedPart);
		formdata.append('dataname', data_name);
		formdata.append('lastpart', is_last);
		console.log('Sending Chunk (Start - End): ' + start + ' ' + end);

		xhr.send(formdata);
	}

	/**
	 * Formalize file.slice
	 */

	function slice(file, start, end) {
	  var slice = file.mozSlice ? file.mozSlice :
				  file.webkitSlice ? file.webkitSlice :
				  file.slice ? file.slice : noop;
	  
	  return slice.bind(file)(start, end);
	}

	function updateProgress(event) {
		if (event.lengthComputable) {
			var value = event.loaded / event.total * 100;
			//console.log(value);
		} else {
			console.log('lengthComputable failed');
		}
	}
	
	})();