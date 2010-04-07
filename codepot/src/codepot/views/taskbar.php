
<?php 
function show_taskbar ($con, $loginid, $issysadmin)
{ 
	print '<div class="taskbar">';

	print '<div class="boxb">';

	if (isset($loginid) && $loginid != '')
	{
		print anchor ('user/home', htmlspecialchars($loginid));

		$hex = $con->converter->AsciiToHex (current_url());
		print anchor ("main/signout/{$hex}", $con->lang->line('Sign out'));
	}
	else
	{
		print form_open('main/index');

		$user_name = "";
		$user_pass = "";

		//$hex = $con->converter->AsciiToHex (current_url());
		print form_hidden ('user_url', set_value ('user_url', current_url()));
		//print anchor ("main/signin/{$hex}", $con->lang->line('Sign in'));

		print form_label($con->lang->line('Username').' ', 'user_name');
		print form_input ('user_name', set_value ('user_name', $user_name), 'size=12');
		print '&nbsp;';
		print form_label($con->lang->line('Password').' ', 'user_pass');
		print form_password ('user_pass', set_value ('user_pass', $user_pass), 'size=12');
		print '&nbsp;';
		print form_submit ('login', $con->lang->line('Sign in'), 'class="button"');

		print form_close();
	}
	print '</div>';

	print '<div class="boxa">';
	print anchor ('site/home', $con->lang->line('Home'));
	print anchor ('project/catalog', $con->lang->line('Projects'));
	if ($issysadmin)
		print anchor ('site/catalog', $con->lang->line('Administration'));
	print '</div>';

	print '</div>';
}

show_taskbar ($this, $login['id'], $login['sysadmin?']);
?>


