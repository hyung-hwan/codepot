
<?php 
function show_taskbar ($con, $login)
{ 
	print '<div class="taskbar">';

	print '<div class="boxb">';

	if (isset($login['id']) && $login['id'] != '')
	{
		$title = (isset($login['email']) && $login['email'] != '')?
			('title=' . htmlspecialchars($login['email'])): '';

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

		print anchor ('user/home', htmlspecialchars($login['id']), $title);

		$hex = $con->converter->AsciiToHex (current_url());
		print anchor ("main/signout/{$hex}", $con->lang->line('Sign out'));
	}
	else
	{

//print '<div id="taskbar_signin_panel">';

		print form_open('main/signin', array('id' => 'taskbar_signin_form'));

		print form_fieldset();
//print '<div id="taskbar_signin_form_panel">';

		$user_name = "";
		$user_pass = "";

		print form_hidden (
			'user_url', 
			set_value ('user_url', current_url())
		);

		print form_label(
			$con->lang->line('Username'), 
			'taskbar_user_name'
		);
		print '&nbsp;';
		print form_input (
			'user_name', 
			set_value ('user_name', $user_name), 
			'size="12" id="taskbar_user_name"'
		);

		print '&nbsp;';
		print form_label (
			$con->lang->line('Password'),
			'taskbar_user_pass'
		);
		print '&nbsp;';
		print form_password (
			'user_pass',
			set_value ('user_pass', $user_pass),
			'size="12" id="taskbar_user_pass"'
		);

		print '&nbsp;';
		print form_submit (
			'login', 
			$con->lang->line('Sign in'), 
			'class="button" id="taskbar_signin_ok_button"'
		);
//print '</div>';

//print '<div id="taskbar_signin_button_panel">';
//		print '<a href="#" id="taskbar_signin_button">';
//		print $con->lang->line('Sign in');
//		print '</a>';
//print '</div>';

		print form_fieldset_close();
		print form_close();

//print '</div>';

	}
	print '</div>'; // boxb

	print '<div class="boxa">';
	print anchor ('site/home', $con->lang->line('Home'));
	print anchor ('project/catalog', $con->lang->line('Projects'));
	if ($login['sysadmin?'])
		print anchor ('site/catalog', $con->lang->line('Administration'));
	print '</div>';

	print '</div>';
}
?>

<script type="text/javascript">
/*
$(function () {
	$("#taskbar_signin_form_panel").hide();

	btn_label = "<?=$this->lang->line('Sign in')?>";
	btn = $("#taskbar_signin_button").button({"label": btn_label}).click (function () {
		if ($("#taskbar_signin_form_panel").is(":visible"))
		{
			$("#taskbar_signin_form_panel").hide("slide",{direction: 'right'},200);
		}
		else
		{
			$("#taskbar_signin_form_panel").show("slide",{direction: 'right'},200);
		}
	});

	$("#taskbar_signin_ok_button").button();
}); */
</script> 

<?php
show_taskbar ($this, $login);
?>


