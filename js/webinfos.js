function filechoosen (fileid, typefiletab, msgerror) {	// Check if uploaded file is in the requirement
	var test = verifFileExtension(fileid, typefiletab, msgerror);
	if (test == false) {
		document.getElementById(fileid).value=null;
	}
}

function getExtension(filename){	// Get the file format
        var parts = filename.split(".");
        return (parts[(parts.length-1)]);
}    


// check uploaded file format
// champ : file button id
// listeExt : allowed format list
function verifFileExtension(champ, exttab, msgerror){
	filename = document.getElementById(champ).value.toLowerCase();
	fileExt = getExtension(filename);
	listExt= exttab;
	ext='';
		for (var i=0; i<listExt.length; i++) {
			if ((i+1) != listExt.length) {
				ext=ext + '.' + listExt[i] + '/';
			}
			else {
				ext=ext + '.' + listExt[i];
			}
			if ( fileExt == listExt[i] ) {
				return (true);
			}
		}
	switch(msgerror) {
    case "imgerrormsg":
        alert(object_name.imgerrormsg);
        break;
    case "imgerrormsg2":
        alert(object_name.imgerrormsg2);
        break;
    case "viderrormsg":
        alert(object_name.viderrormsg);
        break;
	}
	return (false);
}	

function countchar(label, count){  // Count the number of character left before reaching the limit. 
 	var charleft= (document.getElementById(label).maxLength - document.getElementById(label).value.length);
 	if (charleft > 1) {
 		document.getElementById(count).innerHTML = charleft + ' ' + object_name.charsmsg;
 	}
 	else {
 		document.getElementById(count).innerHTML = charleft + ' ' + object_name.charmsg;
 	}
 }

// drag & drop interactions
  (function(window) {
    function triggerCallback(e, callback) {
      if(!callback || typeof callback !== 'function') {
        return;
      }
      var files;
      if(e.dataTransfer) {
        files = e.dataTransfer.files;
        document.getElementById('dropused').value="1";
      } else if(e.target) {
        files = e.target.files;
        document.getElementById('dropused').value="0";
      }
      callback.call(null, files);
      
      dropfiles=files;
    }
   
    function makeDroppable(ele, callback) {
      var input = document.getElementById('webinfos_attachment');
     	 input.addEventListener('change', function(e) {
       	 triggerCallback(e, callback);
    	  });
     	 ele.appendChild(input);
      
      ele.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        ele.classList.add('dragover');
      });

      ele.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        ele.classList.remove('dragover');
      });

      ele.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        ele.classList.remove('dragover');
        triggerCallback(e, callback);
      });
      
      ele.addEventListener('click', function() {
        input.value = null;
        input.click();
      });
    }
    window.makeDroppable = makeDroppable;
  })(this);
  
  var element = document.querySelector('.droppable');
function callback(files) {
       var output = document.querySelector('.output');
      output.innerHTML = object_name.newlist;
      var nb = 0;
      for(var i=0; i<files.length; i++) {
      	nb++;
      	if (nb==1) {
      		 output.innerHTML += '<p>';
      	}
        output.innerHTML += files[i].name;
        if ((i+1) != files.length) {
        	 output.innerHTML += ' | ';
      	}
      	if (nb==3) {
      		 output.innerHTML += '</p>';
      		 nb=0;
      	}
      }
 } 
    // upload dragged files 
    function ajaxupload () {
 		if (document.getElementById('dropused').value=="1") { 
 		var formData = new FormData();
 		for(var i=0; i<dropfiles.length; i++) {
      		formData.append('dragfiles['+ i + ']', dropfiles[i]);
      		if (document.getElementById('webinfos_msg') != null) {
      			formData.append('editmsg', document.getElementById('webinfos_msg').value);
      		}
      		else {
      			formData.append('editmsg', "0");
      		}
 		}
 		 $.ajax({
  		  url: '../wp-content/plugins/infos/js/ajax/attach.php',
  		  method: 'post',
  		  data: formData,
   		 processData: false,
  		  contentType: false,
  		  success: function(returnData) {
   			 document.getElementById('custom_submit').click();
  			}});
 		}
 		else {
 			document.getElementById('custom_submit').click();
 		}	
 	}
// drag & drop interactions applies when the droppable area exists 
if (document.getElementById('webinfos_attachment') != null){
makeDroppable(element, callback);
}