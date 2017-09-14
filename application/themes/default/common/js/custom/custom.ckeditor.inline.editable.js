/**------------------------------------------------------------------------------------------------------------------------------
 * [NAME]
 * custom.ckeditor.inline.editable.js
 *
 * [DETAILS]
 * generic hnadler for ckeditor inline editable text. It initiates the editor and then send data via ajax for saving
 *
 * noty.js notifications are optional (commented out below)
 *
 *
 * [EVENTS]
 * edit button clicked
 * save button clicked
 *
 * [DEPENDENCIES]
 * jquery.js, noty.js, ckeditor
 *
 * [NOTES]
 * You can have multiple inline editors, simply make sure the "id" and "data-ckeditor-inline-editable-div" are unique
 *
 * [AUTHOR]
 * nextloop
 *
 */
/*[SAMPLE HTML CODE]---------------------------------------------------------------------------------------------------------------
 <div class="some-optional-class">
 <div id="ckeditor-toolbar-location-description">
 <!--toolbat will dynamically be added here-->
 </div>
 <div class="x-section-content" contenteditable="false" id="ckeditor-edit-task-description"> [task.tasks_description;htmlconv=no] </div>
 <div class="task-ckeditor-toggle-show" style="text-align:right;">
 <button class="btn btn-info ckeditor-toggle-inline-editable"
 data-ckeditor-inline-editable-div="ckeditor-edit-task-description"
 data-lang-start-editing="[lang.lang_edit_description]"
 data-lang-finish-editing="[lang.lang_finish_editing]"
 data-content-id="[task.tasks_id]"
 data-ckeditor-toolbar="Plain"
 data-ckeditor-toolbar-location="ckeditor-toolbar-location-description"
 data-ajax-url="[conf.site_url]/admin/ajaxtwo/editable-task-description">Edit Description</button>
 </div>
 </div>
 ---------------------------------------------------------------------------------------------------------------------------------*/
$(document).ready(function() {

	/**------------------------------------------------------------------------
	 * initiate instance of ckeditor
	 *------------------------------------------------------------------------*/
	var editor;
	CKEDITOR.on('instanceReady', function(ev) {
		editor = ev.editor;
		// you can also add more config for this instance of CKE here
		// e.g. editor.setReadOnly(false);
	});

	/**------------------------------------------------------------------------
	 * edit text button has been clicked
	 *------------------------------------------------------------------------*/
	$('.ckeditor-toggle-inline-editable').click(function(e) {

		//get vars
		var id_editable_div = $(this).attr('data-ckeditor-inline-editable-div');
		var editable_div = '#' + id_editable_div;
		var toolbar_location = $(this).attr('data-ckeditor-toolbar-location');

		//language
		var lang_start_editing = $(this).attr('data-lang-start-editing');
		var lang_finish_editing = $(this).attr('data-lang-finish-editing');

		//language defaults
		var lang_start_editing = (lang_start_editing === '') ? 'Start Editing' : lang_start_editing;
		var lang_finish_editing = (lang_finish_editing === '') ? 'Finish Editing' : lang_finish_editing;

		//ckeditor toolbar style
		var toobar_style = $(this).attr('data-ckeditor-toolbar');

		//enable/disable ckeditor - toggle edit/save button text
		if ($(editable_div).attr('contenteditable') === 'true') {

			//disable ckeditor
			$(editable_div).attr('contenteditable', 'false');
			editor.destroy();

			//remove border from text div
			$(editable_div).css('border', 'none');

			//toggle button text and style
			$(this).addClass('btn-info');
			$(this).removeClass('btn-success');
			$(this).text(lang_start_editing);

			//get vars
			var new_content = $(editable_div).html();
			var content_id = $(this).attr('data-content-id');
			var ajax_url = $(this).attr('data-ajax-url');

			//optional vars
			var post_var1 = $(this).attr('data-post-var1');
			var post_var2 = $(this).attr('data-post-var2');

			//send changes to server
			saveEditorChanges(ajax_url, new_content, content_id, post_var1, post_var2);

			//destroy ckeditor instance
			if (CKEDITOR) {
				if (CKEDITOR.instances[id_editable_div]) {
					CKEDITOR.instances[id_editable_div].destroy();
				}
			}

		} else {

			/**------------------------------------------------------------------------
			 * create an instance of ckeditot
			 *------------------------------------------------------------------------*/
			$(editable_div).attr('contenteditable', 'true');
			CKEDITOR.disableAutoInline = true;
			CKEDITOR.inline(id_editable_div, {
				removePlugins : 'stylescombo',
				toolbar : toobar_style,
				uiColor : '#ffffff',
				startupFocus : true,
				sharedSpaces : {
					top : toolbar_location
				}
			});

			//add border to text div
			$(editable_div).css('border', '1px solid #b6b6b6');

			//toggle button text and style
			$(this).text(lang_finish_editing);
			$(this).removeClass('btn-info');
			$(this).addClass('btn-success');
		}
	});

	/**------------------------------------------------------------------------
	 * send changes to server for saving
	 *------------------------------------------------------------------------*/
	function saveEditorChanges(ajax_url, new_content, content_id, post_var1, post_var2) {

		//-------make ajax call-------
		ajax_request_comments = $.ajax({
			type : 'post',
			url : ajax_url,
			dataType : 'json',
			data : 'new_content=' + new_content + '&content_id=' + content_id + '&post_var1=' + post_var1 + '&post_var2=' + post_var2,

			//------update was successful, get the response from the server------
			success : function(data) {

				//get json response
				var result = data.result;
				var message = data.message;

				//optional noty notification
				/*
				 if (result === 'ok') {
				 noty({
				 text : message,
				 layout : 'bottomRight',
				 type : 'information',
				 timeout : 2500
				 });
				 } else {
				 noty({
				 text : message,
				 layout : 'bottomRight',
				 type : 'information',
				 timeout : 2500
				 });
				 }
				 */
			},

			//------update was not successful------
			error : function(data) {
				//noty error notification
				noty({
					text : 'Request could not be completed',
					layout : 'bottomRight',
					type : 'warning',
					timeout : 2500
				});
			}
		});

	}

});
