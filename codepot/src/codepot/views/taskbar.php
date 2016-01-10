
<?php 
function show_taskbar ($con, $login)
{ 
	print '<div class="taskbar">';
	print "\n";

	print '<div class="boxb">';

	if (isset($login['id']) && $login['id'] != '')
	{
		$title = (isset($login['email']) && $login['email'] != '')?
			htmlspecialchars($login['email']): '';

		$hex = $con->converter->AsciiToHex (current_url());
		print form_open("main/signout/{$hex}", array('id' => 'taskbar_signinout_form'));

		/*
		// attempt to load the user icon regardless of its upload state.
		// if it has not been uploaded, it won't be found. 
		// check a file system may be faster than checking the database.
		$icon_src = '';
		$icon_path = CODEPOT_USERICON_DIR . '/' . $login['id'] . '.png';
		$icon_image = @file_get_contents($icon_path);
		if ($icon_image)
		{
			$icon_src = sprintf (
				'<img class="user_icon_img" src="data:%s;base64,%s" alt="" />',
				mime_content_type($icon_path),
				base64_encode($icon_image)
			);
		}
		print $icon_src;
		*/
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $con->converter->AsciiToHex($login['id']));
		print "<img src='{$user_icon_url}' class='user_icon_img' id='taskbar_user_icon'/>";

		print anchor ('user/home', htmlspecialchars($login['id']), array('title' => $title, 'id' => 'taskbar_user_title'));


		print '&nbsp;';
		printf ('<a href="#" id="taskbar_signinout_button">%s</a>', $con->lang->line('Sign out'));
		print form_close();
	}
	else
	{
		print '<div id="taskbar_signin_container">';

		print '<div id="taskbar_signin_error"></div>';

		print '<div id="taskbar_signin_form">';

		print form_input (
			'user_name', set_value ('user_name', ''), 
			"size='16' id='taskbar_user_name' placeholder={$con->lang->line('Username')}"
		);

		print form_password (
			'user_pass', set_value ('user_pass', ''),
			"size='16' id='taskbar_user_pass' placeholder={$con->lang->line('Password')}"
		);
		print '</div>';
		print '</div>';

		printf ('<a href="#" id="taskbar_signinout_button">%s</a>', $con->lang->line('Sign in'));
	}
	print '</div>'; // boxb

	print '<div class="boxa">';
	print '<ul>';
	print '<li>';
	print anchor ('site/home', $con->lang->line('Home'));
	print '</li>';
	print '<li>';
	print anchor ('project/catalog', $con->lang->line('Projects'));
	print '</li>';
	print '<li><span class="ui-widget">';
	print " <input id='taskbar_project_to_find' placeholder='{$con->lang->line('Project ID')}' size=40>";
	print '</span></li>';
	if ($login['sysadmin?'])
	{
		print '<li>';
		print anchor ('site/catalog', $con->lang->line('Administration'));
		print '</li>';
	}
	print '</ul>';

	print '</div>'; // boxa

	print '</div>';
}
?>

<script type="text/javascript">


function ready_to_signinout()
{
<?php if (isset($login['id']) && $login['id'] != ''): ?>
	// signed in already. can signout anytime.
	return true;
<?php else: ?>
	// not signed-in yet. both username and password must be filled.
	return $("#taskbar_user_name").val() != "" && $("#taskbar_user_pass").val();
<?php endif; ?>
}

var taskbar_signin_in_progress = 0;

$(function () {
	$('#taskbar_signin_container').dialog ({
		title: '<?php print $this->lang->line('Sign in'); ?>',
		resizable: true,
		autoOpen: false,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			'<?php print $this->lang->line('OK')?>': function () {
				if (taskbar_signin_in_progress) return;

				if (!!window.FormData)
				{
					// FormData is supported
					taskbar_signin_in_progress = true;

					var form_data = new FormData();
					form_data.append ('user_name', $('#taskbar_user_name').val());
					form_data.append ('user_pass', $('#taskbar_user_pass').val());

					$('#taskbar_signin_container').dialog('disable');
					$.ajax({
						url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/main/xhr_signin"; ?>'),
						type: 'POST',
						data: form_data,
						mimeType: 'multipart/form-data',
						contentType: false,
						processData: false,
						cache: false,

						success: function (data, textStatus, jqXHR) { 
							taskbar_signin_in_progress = false;

							$('#taskbar_signin_container').dialog('enable');
							if (data == 'ok') 
							{
								$('#taskbar_signin_container').dialog('close');
								// refresh the page to the head revision
								$(location).attr ('href', '<?php print current_url(); ?>');
							}
							else
							{
								$('#taskbar_signin_error').text(codepot_htmlspecialchars('<?php print $this->lang->line('MSG_SIGNIN_FAILURE')?>'));
							}
						},

						error: function (jqXHR, textStatus, errorThrown) { 
							taskbar_signin_in_progress = false;
							$('#taskbar_signin_container').dialog('enable');
							var errmsg = '';
							if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
							if (errmsg == '' && textStatus != null) errmsg = textStatus;
							if (errmsg == '') errmsg = 'Unknown error';
							$('#taskbar_signin_error').text(codepot_htmlspecialchars(errmsg));
						}
					});
				}
				else
				{
					$('#taskbar_signin_error').text('NOT SUPPORTED');
				}

			},
			'<?php print $this->lang->line('Cancel')?>': function () {
				if (taskbar_signin_in_progress) return;
				$('#taskbar_signin_container').dialog('close');
			}
		},

		beforeClose: function() { 
			// if importing is in progress, prevent dialog closing
			return !taskbar_signin_in_progress;
		}
	});

	$("#taskbar_user_name").button().bind ('keyup', function(e) {
		if (e.keyCode == 13) 
		{
			var buttons = $("#taskbar_signin_container").dialog("option", "buttons");
			buttons[Object.keys(buttons)[0]](); // trigger the first button
		}
	});
	$("#taskbar_user_pass").button().bind ('keyup', function(e) {
		if (e.keyCode == 13) 
		{
			var buttons = $("#taskbar_signin_container").dialog("option", "buttons");
			buttons[Object.keys(buttons)[0]](); // trigger the first button
		}
	});

	$("#taskbar_signinout_button").button().click (function() {
		<?php if (isset($login['id']) && $login['id'] != ''): ?>
		if (ready_to_signinout()) $("#taskbar_signinout_form").submit();
		<?php else: ?>
		$('#taskbar_signin_container').dialog('open');
		<?php endif; ?>
		return false;
	});

	$("#taskbar_project_to_find").button().autocomplete({
		minLength: 1, // is this too small?
		delay: 1000,
		source: function (request, response) {

			var term = codepot_string_to_hex(request.term);

			$.ajax({
				url: codepot_merge_path("<?php print site_url(); ?>", "/project/enjson_quickfind/" + term),
				dataType: "json",
				success: function(data) { response(data); },
			});
		},
		select: function( event, ui ) {
			$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", "/project/home/" + ui.item.id));
			//ui.item.value , ui.item.id ,  this.value
		}
	});
});

</script> 

<?php
show_taskbar ($this, $login);
?>


