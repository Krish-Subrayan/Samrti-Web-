//--ACTION ON TICK BOXES--------------------------------------------------------------------------------------------------
$(document).ready(function() {

	/** ------------------------------------------------------
	 *  Task has been unticked
	 * -----------------------------------------------------*/
	$('input').on('ifChecked', function(event) {
		//url
		var ajax_url = $(this).attr('data-url');

		//label containers
		original_task_status_container = $(this).attr('data-status-holder-original');
		pending_task_status_container = $(this).attr('data-status-holder-pending');
		completed_task_status_container = $(this).attr('data-status-holder-completed');
		behind_task_status_container = $(this).attr('data-status-holder-behind');
		main_task_container = $(this).attr('data-task-main-container');

		//make call
		updateTaskStatus(ajax_url + '/completed');
	});

	/** ------------------------------------------------------
	 *  Task has been ticked
	 * -----------------------------------------------------*/
	$('input').on('ifUnchecked', function(event) {

		//url
		var ajax_url = $(this).attr('data-url');

		//label containers
		original_task_status_container = $(this).attr('data-status-holder-original');
		pending_task_status_container = $(this).attr('data-status-holder-pending');
		completed_task_status_container = $(this).attr('data-status-holder-completed');
		behind_task_status_container = $(this).attr('data-status-holder-behind');
		
		main_task_container = $(this).attr('data-task-main-container');

		//make call
		updateTaskStatus(ajax_url + '/pending');
	});
});

/** ---------------------------------------------------------------------------------------------------------------------
 *  Ajax call to server
 * ---------------------------------------------------------------------------------------------------------------------*/
function updateTaskStatus(ajax_url) {

	//-----kill any similar ajax request thats already running (optional)----
	if ( typeof ajax_request_one !== 'undefined' && ajax_request_one && ajax_request_one.readyState !== 4) {
		ajax_request.abort();
	}

	//-----make ajax request----------------------
	var ajax_request = $.ajax({
		type : 'get',
		url : ajax_url,
		dataType : 'json',

		/**------------------------------------------------------------------------
		 * update was successful (header status:200)
		 *------------------------------------------------------------------------*/
		success : function(data) {

			/**
			 * get a json response
			 */
			var task_status = data.task_status;

			//disable all task status container
			$('#' + original_task_status_container).hide();
			$('#' + pending_task_status_container).hide();
			$('#' + completed_task_status_container).hide();
			$('#' + behind_task_status_container).hide();
			$('#' + main_task_container).removeClass('pending');
			$('#' + main_task_container).removeClass('behind');
			$('#' + main_task_container).removeClass('completed');

			//task is now completed
			if (task_status == 'completed') {
				$('#' + completed_task_status_container).show();
				$('#' + main_task_container).addClass('completed');
			}
			//task is now pending
			if (task_status == 'pending') {
				$('#' + pending_task_status_container).show();
				$('#' + main_task_container).addClass('pending');
			}
			//task is now behind schedule
			if (task_status == 'behind schedule') {
				$('#' + behind_task_status_container).show();
				$('#' + main_task_container).addClass('behind');
			}
		},

		/**------------------------------------------------------------------------
		 * update was NOT successful (header status:400)
		 *------------------------------------------------------------------------*/
		error : function(data) {

			/**
			 * make data json ready
			 */
			var data = $.parseJSON(data);
			var message = data.message;

			//get error message if any
			if (message == '') {
				message = 'Request could not be completed';
			}

			noty({
				text : message,
				layout : 'bottomRight',
				type : 'warning',
				timeout : 2500
			});
		}
	});
}