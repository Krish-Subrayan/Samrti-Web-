$(document).ready(function() {
	$("#progress-bar").click(function() {
		$("#results-container").hide();
	});
	var sizeBox = document.getElementById('file-info-div');
	var progress = document.getElementById('progress-bar');
	var uploader = new ss.SimpleUpload({
		button : 'upload-button',
		url : upload_url,
		name : 'uploadedfile',
		responseType : 'json',
		allowedExtensions : js_allowed_types,
		maxSize : js_file_size_limit,
		hoverClass : '',
		focusClass : '',
		disabledClass : '',
		method : 'POST',
		/**----------------------------------------------------------
		 * ERROR: wrong file type
		 * ---------------------------------------------------------*/
		onExtError : function(filename, extension) {
			$("#results-container").show();
			$("#results-container").text(lang_upload_invalid_filetype);
		},
		/**----------------------------------------------------------
		 * ERROR: wrong file size
		 * ---------------------------------------------------------*/
		onSizeError : function(filename, fileSize) {
			$("#results-container").show();
			$("#results-container").text(lang_upload_invalid_filesize);
		},
		/**----------------------------------------------------------
		 * UPLOADING: fie is currently uploading
		 * ---------------------------------------------------------*/
		onSubmit : function(filename, extension) {
			$("#progress-container").show();
			this.setFileSizeBox(sizeBox);
			this.setProgressBar(progress);
			$("#results-container").hide();
			$("#progress-bar").show();
		},
		/**----------------------------------------------------------
		 * SUCCESS: Everything went ok, make DOM changes etc
		 * ---------------------------------------------------------*/
		onComplete : function(filename, response) {

			//get response data
			result = response.success;
			message = response.message;
			redirect_url = response.redirect_url;

			$("#progress-container").hide();

			/** file uploaded ok - set DOM values, show next buttons, fill hidden form field, etc etc*/
			if (result == 1) {
				//reload the window
				if (redirect_url != '') {
					window.location.replace(redirect_url);
				}
				return true;
			} else {
				$("#results-container").show();
				$("#results-container").text(message);
			}
		},
		onError : function(filename, errorType, status, statusText, response, uploadBtn) {
			message = response.message;
			$("#progress-container").hide();
			if (message == '' || message == false || message == null) {
				message = lang_upload_system_error;
			}
			$("#results-container").show();
			$("#results-container").text(message);
		}
	});
});

/**----------------------------------------------------------
 * DELETE FILE: link/icon to delete file has been clicked
 * Actual file deletion will be done during server side
 * clenups
 * ---------------------------------------------------------*/
$(document).ready(function() {
	$("#delete-uploaded-file").click(function(e) {
		e.preventDefault();
		$("#results-container-extended").hide();
		$("#results-container").hide();
		$("#form-submit-button-container").hide();
		$("#progress-bar").hide();
		$("#progress-container").show();
		$("#upload-button-container").show();
		$('#files_name').val('');
		$('#files_size').val('');
		$('#files_foldername').val('');
		$('#files_extension').val('');
	});
});
