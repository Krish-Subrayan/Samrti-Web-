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
		onExtError : function(filename, extension) {
			$("#results-container").show();
			$("#results-container").text(lang_upload_invalid_filetype);
		},
		onSizeError : function(filename, fileSize) {
			$("#results-container").show();
			$("#results-container").text(lang_upload_invalid_filesize);
		},
		onSubmit : function(filename, extension) {
			$("#progress-container").show();
			this.setFileSizeBox(sizeBox);
			this.setProgressBar(progress);
			$("#results-container").hide();
		},
		onComplete : function(filename, response) {
			result = response.success;
			file_url = response.fileurl;
			message = response.message;
			file_name = response.file_name;
			file_size = response.file_size;
			file_foldername = response.file_foldername;
			file_extension = response.file_extension;
			file_folder_path = response.file_folder_path;
			$("#progress-container").hide();
			if (result) {
				$("#results-container-extended").show();
				$("#new-file-name").text(file_name);
				$("#progress-container").hide();
				$("#upload-button-container").hide();
				$("#form-submit-button-container").css('display', 'inline-block');
				$('#files_name').val(file_name);
				$('#files_size').val(file_size);
				$('#files_foldername').val(file_foldername);
				$('#files_extension').val(file_extension);
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
$(document).ready(function() {
	$("#delete-uploaded-file").click(function(e) {
		e.preventDefault();
		$("#results-container-extended").hide();
		$("#results-container").hide();
		$("#upload-button-container").show();
		$('#files_name').val('');
		$('#files_size').val('');
		$('#files_foldername').val('');
		$('#files_extension').val('');
	});
}); 