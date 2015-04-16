
<?php 
function show_taskbar ($con, $login)
{ 
	print '<div class="taskbar">';

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
		//print form_submit (
		//	'login', 
		//	$con->lang->line('Sign out'), 
		//	'class="button" id="taskbar_signinout_button"'
		//);
		printf ('<a href="#" id="taskbar_signinout_button">%s</a>', $con->lang->line('Sign out'));
		print form_close();
	}
	else
	{
		print form_open('main/signin', array('id' => 'taskbar_signinout_form'));

		print form_fieldset();

		$user_name = "";
		$user_pass = "";

		print form_hidden (
			'user_url', 
			set_value ('user_url', current_url())
		);

		/*
		print form_label(
			$con->lang->line('Username'), 
			'taskbar_user_name'
		);
		print '&nbsp;';
		*/
		print form_input (
			'user_name', 
			set_value ('user_name', $user_name), 
			"size='16' id='taskbar_user_name' placeholder={$con->lang->line('Username')}"
		);

		print '&nbsp;';
		/*
		print form_label (
			$con->lang->line('Password'),
			'taskbar_user_pass'
		);
		print '&nbsp;';
		*/
		print form_password (
			'user_pass',
			set_value ('user_pass', $user_pass),
			"size='16' id='taskbar_user_pass' placeholder={$con->lang->line('Password')}"
		);

		print '&nbsp;';
		//print form_submit (
		//	'login', 
		//	$con->lang->line('Sign in'), 
		//	'class="button" id="taskbar_signinout_button"'
		//);
		printf ('<a href="#" id="taskbar_signinout_button">%s</a>', $con->lang->line('Sign in'));

		print form_fieldset_close();
		print form_close();
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

	print '</div>';

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

$(function () {
	$("#taskbar_user_name").button().bind ('keyup', function(e) {
		if (e.keyCode == 13) {
			if (ready_to_signinout()) $("#taskbar_signinout_form").submit();
		}
	});
	$("#taskbar_user_pass").button().bind ('keyup', function(e) {
		if (e.keyCode == 13) {
			if (ready_to_signinout()) $("#taskbar_signinout_form").submit();
		}
	});

	$("#taskbar_signinout_button").button().click (function() {
		if (ready_to_signinout()) $("#taskbar_signinout_form").submit();
	});

	$("#taskbar_project_to_find").button().autocomplete({
		minLength: 1, // is this too small?
		source: function (request, response) {

			var term = codepot_string_to_hex(request.term);

			$.ajax({
				url: "<?php print site_url(); ?>/project/quickfind_json/" + term,
				dataType: "json",
				success: function(data) { response(data); },
			});
		},
		select: function( event, ui ) {
			$(location).attr ('href', "<?php print site_url(); ?>/project/home/" + ui.item.id);
			//ui.item.value , ui.item.id ,  this.value
		}
	});
});

</script> 

<?php
show_taskbar ($this, $login);
?>


