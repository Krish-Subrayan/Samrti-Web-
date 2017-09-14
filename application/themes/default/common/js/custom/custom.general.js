//--ACTION ON TICK BOXES--------------------------------------------------------------------------------------------------
$(document).ready(function() {
	//monitor selections
	$('input').on('ifChecked', function(event) {

		//which option was selected
		var which_toggle = $(this).attr('data-toogle');

		//toggle owner details section
		if ( typeof which_toggle !== 'undefined' && which_toggle == 'move-hashtag') {
			//some function
		}
		
	});
});

/** custom.bootbox.confirm.popup.js */
$(document).ready(function() {
	$('.bootbox-confirm').click(function(e) {
		var link = $(this).attr("href");
		e.preventDefault();
		bootbox.confirm("Are you sure?", function(result) {
			if (result) {
				document.location.href = link;
			}
		});
	});
});

/** custom.ajax.delete.records.js */
$(document).ready(function() {
	$(".ajax-delete-record").click(function() {
		var data_mysql_record_id = $(this).attr("data-mysql-record-id");
		var data_mysql_record_id2 = $(this).attr("data-mysql-record-id2");
		var data_mysql_record_id3 = $(this).attr("data-mysql-record-id3");
		var data_mysql_record_id4 = $(this).attr("data-mysql-record-id4");
		var data_dialogue_message = $(this).attr("data-dialogue-message");
		var data_ajax_url = $(this).attr("data-ajax-url");
		var data_mysql_table_name = $(this).attr("data-mysql-table-name");
		var parent = $(this).parents("tr:first");
		if (data_dialogue_message == '') {
			var popup_message = 'please confirm';
		} else {
			var popup_message = data_dialogue_message;
		}
		$.ajax({
			type : 'post',
			url : data_ajax_url,
			dataType : 'json',
			data : 'data_mysql_record_id=' + data_mysql_record_id + '&data_mysql_table_name=' + data_mysql_table_name + '&data_mysql_record_id2=' + data_mysql_record_id2 + '&data_mysql_record_id3=' + data_mysql_record_id3 + '&data_mysql_record_id4=' + data_mysql_record_id4,
			success : function(data) {
				$ajax_response = data.message;
				if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
					$ajax_response = 'Request has been completed';
				}
				parent.slideUp(300, function() {
					parent.remove();
				});
				setTimeout(function() {
					noty({
						text : $ajax_response,
						layout : 'bottomRight',
						type : 'information',
						timeout : 1500
					});
				}, 300);
			},
			error : function(data) {
				var data = data.responseJSON;
				$ajax_response = data.message;
				if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
					$ajax_response = 'Error!- This request could not be completed';
				}
				noty({
					text : '' + $ajax_response,
					layout : 'bottomRight',
					type : 'warning',
					timeout : 1500
				});
			}
		});
	});
	$(".ajax-delete-record").popConfirm();
});
/** custom.ajax.delete.content.js */
$(document).ready(function() {
	$(".ajax-delete-content").click(function() {
		var data_mysql_record_id = $(this).attr("data-mysql-record-id");
		var data_mysql_record_id2 = $(this).attr("data-mysql-record-id2");
		var data_dialogue_message = $(this).attr("data-dialogue-message");
		var data_ajax_url = $(this).attr("data-ajax-url");
		var parent_div = $(this).attr("data-parent-div-id");
		if (data_dialogue_message == '') {
			var popup_message = 'please confirm';
		} else {
			var popup_message = data_dialogue_message;
		}
		$.ajax({
			type : 'post',
			url : data_ajax_url,
			dataType : 'json',
			data : 'data_mysql_record_id=' + data_mysql_record_id + '&data_mysql_record_id2=' + data_mysql_record_id2,
			success : function(data) {
				$ajax_response = data.message;
				if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
					$ajax_response = 'Request has been completed';
				}
				$('#' + parent_div).slideUp(300, function() {
					$('#' + parent_div).remove();
				});
				setTimeout(function() {
					noty({
						text : $ajax_response,
						layout : 'bottomRight',
						type : 'information',
						timeout : 1500
					});
				}, 300);
			},
			error : function(data) {
				var data = data.responseJSON;
				$ajax_response = data.message;
				if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
					$ajax_response = 'Error!- This request could not be completed';
				}
				noty({
					text : '' + $ajax_response,
					layout : 'bottomRight',
					type : 'warning',
					timeout : 1500
				});
			}
		});
	});
	$(".ajax-delete-content").popConfirm();
});
/** custom.submit.forms.js */
$(document).ready(function() {
	var submit_form = 0;
	$('.form-button').click(function(e) {
		e.preventDefault();
		var button_action = $(this).attr("data-action");
		var form_id = $(this).closest("form").attr("id");
		if (form_id == '') {
			form_id = 'form';
		}
		var data_bootbox_message = $(this).attr("data-bootbox-message");
		if (data_bootbox_message == '') {
			var data_bootbox_message = 'please confirm';
		}
		$('input[name="which_button"]').val(button_action);
		bootbox.confirm(data_bootbox_message, function(result) {
			if (result) {
				$("#" + form_id).submit();
			}
		});
	});
});
/** custom.select.all.js */
$(document).ready(function() {
	$('#selectall').click(function() {
		$('.tickbox').prop('checked', this.checked);
	});
	$('.tickbox').change(function() {
		var check = ($('.tickbox').filter(":checked").length == $('.tickbox').length);
		$('#selectall').prop("checked", check);
	});
});
/** custom.editable.js */
$(document).ready(function() {
	$.fn.editable.defaults.mode = 'popup';
	$('.editable_area').editable();
});
/** custom.ajax.get.milestones.js */
$(document).ready(function() {
	$("#add-tasks-project-list").change(function() {
		var data_mysql_record_id = $(this).val();
		var data_ajax_url = $(this).attr("data-ajax-url");
		if ($.isNumeric(data_mysql_record_id)) {
			var $next = 1;
		} else {
			var $next = 0;
		}
		$("#add-tasks-milestones-list").html('');
		$("#add-tasks-milestones-list").select2("val", "");
		if ($next === 1) {
			$.ajax({
				type : 'post',
				url : data_ajax_url,
				dataType : 'json',
				data : 'data_mysql_record_id=' + data_mysql_record_id,
				success : function(data) {
					ajax_milestones_list = data.list;
					client_id = data.client_id;
					$("#add-tasks-milestones-list").html(ajax_milestones_list);
					$('#add_tasks_client_id').val(client_id);
					$("#add-tasks-milestones-list").select2({
						placeholder : ""
					});
				},
				error : function(data) {
					var data = data.responseJSON;
					$ajax_response = data.message;
					if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
						$ajax_response = 'Error!- This request could not be completed';
					}
					noty({
						text : '' + $ajax_response,
						layout : 'bottomRight',
						type : 'warning',
						timeout : 1500
					});
				}
			});
		}
	});
});
/** custom.ajax.get.invoice.items.js */
$(document).ready(function() {
	$("#invoice_items_id").change(function() {
		$(".toggle-readonly").attr("readonly", "readonly");
		var data_mysql_record_id = $(this).val();
		var data_ajax_url = $(this).attr("data-ajax-url");
		if ($.isNumeric(data_mysql_record_id)) {
			var $next = 1;
		} else {
			var $next = 0;
		}
		$("#invoice-add-items-save").css('display', 'none');
		if (data_mysql_record_id == 0) {
			$("#invoice-add-items-save").css('display', 'block');
			$(".toggle-readonly").removeAttr("readonly");
			$next = 0;
		}
		if ($next === 1) {
			$.ajax({
				type : 'post',
				url : data_ajax_url,
				dataType : 'json',
				data : 'data_mysql_record_id=' + data_mysql_record_id,
				success : function(data) {
					console.log(data);
					invoice_items_title = data.invoice_items_title;
					invoice_items_description = data.invoice_items_description;
					invoice_items_amount = data.invoice_items_amount;
					$(".toggle-readonly").removeAttr("readonly");
					$("#invoice_products_description").val(invoice_items_description);
					$("#invoice_products_rate").val(invoice_items_amount);
					$("#invoice_products_title").val(invoice_items_title);
				},
				error : function(data) {
					var data = data.responseJSON;
					ajax_response = data.message;
					if (ajax_response == '' || typeof ajax_response === 'undefined') {
						ajax_response = lang_requested_item_not_loaded;
					}
					noty({
						text : '' + ajax_response,
						layout : 'bottomRight',
						type : 'warning',
						timeout : 1500
					});
				}
			});
		}
	});
});
/** custom.ajax.toggle.timer.js */
$(document).ready(function() {
	$(".ajax-toggle-timer").click(function() {
		var data_mysql_record_id = $(this).attr("data-mysql-record-id");
		var data_project_id = $(this).attr("data-project-id");
		var data_ajax_url = $(this).attr("data-ajax-url");
		var timer_new_status = $(this).attr("data-timer-new-status");
		var $next = 1;
		if ($next === 1) {
			$.ajax({
				type : 'post',
				url : data_ajax_url,
				dataType : 'json',
				data : 'data_mysql_record_id=' + data_mysql_record_id + '&data_timer_new_status=' + timer_new_status + '&data_project_id=' + data_project_id,
				success : function(data) {
					ajax_response = data.message;
					current_time = data.current_time;
					project_total_time = data.project_total_time;
					if (ajax_response == '' || ajax_response == 'undefined') {
						ajax_response = lang_timer_has_been_updated;
					}
					$("#my-project-time").text(current_time);
					$("#project-timer").text(project_total_time);
					if (timer_new_status == 'running') {
						$("#btn-start-timer").removeClass("visible").addClass("invisible");
						$("#btn-stop-timer").removeClass("invisible").addClass("visible");
					}
					if (timer_new_status == 'stopped') {
						$("#btn-start-timer").removeClass("invisible").addClass("visible");
						$("#btn-stop-timer").removeClass("visible").addClass("invisible");
					}
					setTimeout(function() {
						noty({
							text : ajax_response,
							layout : 'bottomRight',
							type : 'information',
							timeout : 1500
						});
					}, 300);
				},
				error : function(data) {
					var data = data.responseJSON;
					ajax_response = data.message;
					if (ajax_response == '' || ajax_response == 'undefined') {
						ajax_response = lang_request_could_not_be_completed;
					}
					noty({
						text : '' + ajax_response,
						layout : 'bottomRight',
						type : 'warning',
						timeout : 1500
					});
				}
			});
		}
	});
});
/** custom.ajax.refresh.timer.js */
$(document).ready(function() {
	$(".refresh-project-timer").click(function(e) {
		e.preventDefault();
		var data_mysql_record_id = $(this).attr("data-mysql-record-id");
		var data_project_id = $(this).attr("data-project-id");
		var data_ajax_url = $(this).attr("data-ajax-url");
		var $next = 1;
		if ($next === 1) {
			$.ajax({
				type : 'post',
				url : data_ajax_url,
				dataType : 'json',
				data : 'data_mysql_record_id=' + data_mysql_record_id + '&data_project_id=' + data_project_id,
				success : function(data) {
					ajax_response = data.message;
					current_time = data.current_time;
					project_total_time = data.project_total_time;
					if (ajax_response == '' || ajax_response == 'undefined') {
						ajax_response = lang_timer_has_been_updated;
					}
					$("#my-project-time").text(current_time);
					$("#project-timer").text(project_total_time);
					setTimeout(function() {
						noty({
							text : ajax_response,
							layout : 'bottomRight',
							type : 'information',
							timeout : 1500
						});
					}, 300);
				},
				error : function(data) {
					var data = data.responseJSON;
					$ajax_response = data.message;
					if ($ajax_response == '' || typeof $ajax_response === 'undefined') {
						$ajax_response = 'Error!- This request could not be completed';
					}
					noty({
						text : '' + $ajax_response,
						layout : 'bottomRight',
						type : 'warning',
						timeout : 1500
					});
				}
			});
		}
	});
});
/** /custom.ajax.get.invoice.items.js */
$(document).ready(function() {
	$("#invoice_items_id").change(function() {
		$(".toggle-readonly").attr("readonly", "readonly");
		var data_mysql_record_id = $(this).val();
		var data_ajax_url = $(this).attr("data-ajax-url");
		if ($.isNumeric(data_mysql_record_id)) {
			var $next = 1;
		} else {
			var $next = 0;
		}
		$("#invoice-add-items-save").css('display', 'none');
		if (data_mysql_record_id == 0) {
			$("#invoice-add-items-save").css('display', 'block');
			$(".toggle-readonly").removeAttr("readonly");
			$next = 0;
		}
		if ($next === 1) {
			$.ajax({
				type : 'post',
				url : data_ajax_url,
				dataType : 'json',
				data : 'data_mysql_record_id=' + data_mysql_record_id,
				success : function(data) {
					console.log(data);
					invoice_items_title = data.invoice_items_title;
					invoice_items_description = data.invoice_items_description;
					invoice_items_amount = data.invoice_items_amount;
					$(".toggle-readonly").removeAttr("readonly");
					$("#invoice_products_description").val(invoice_items_description);
					$("#invoice_products_rate").val(invoice_items_amount);
					$("#invoice_products_title").val(invoice_items_title);
				},
				error : function(data) {
					var data = data.responseJSON;
					ajax_response = data.message;
					if (ajax_response == '' || typeof ajax_response === 'undefined') {
						ajax_response = lang_requested_item_not_loaded;
					}
					noty({
						text : '' + ajax_response,
						layout : 'bottomRight',
						type : 'warning',
						timeout : 1500
					});
				}
			});
		}
	});
});

