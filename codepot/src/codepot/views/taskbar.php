
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
var TaskbarApp = (function ()
{
	// -------------------------------------------------------------------
	// PRIVATE DATA
	// -------------------------------------------------------------------
	var signin_url = codepot_merge_path('<?php print site_url() ?>', "/main/xhr_signin");
	var project_find_url_base = codepot_merge_path("<?php print site_url(); ?>", "/project/enjson_quickfind/");

	// -------------------------------------------------------------------
	// CONSTRUCTOR
	// -------------------------------------------------------------------
	function App ()
	{
		if (this.constructor != App)
		{
			return new App ();
		}

		this.user_name = $("#taskbar_user_name");
		this.user_pass = $("#taskbar_user_pass");
		this.signinout_form = $("#taskbar_signinout_form");
		this.signinout_button = $("#taskbar_signinout_button");
		this.signin_container = $("#taskbar_signin_container");
		this.signin_error = $("#taskbar_signin_error");
		this.signin_in_progress = false;
		this.signin_ajax = null;

		this.project_to_find = $("#taskbar_project_to_find");
		this.project_find_ajax = null;

		return this;
	}

	// -------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// -------------------------------------------------------------------
	function ready_to_signinout()
	{
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
		// signed in already. can signout anytime.
		return true;
	<?php else: ?>
		// not signed-in yet. both username and password must be filled.
		return this.user_name.val() != "" && this.user_pass.val();
	<?php endif; ?>
	}

	function signinout_on_success (data, textStatus, jqXHR)
	{
		this.signin_in_progress = false;
		this.signin_ajax = null;
		this.signin_container.dialog('enable');

		if (data == 'ok') 
		{
			this.signin_container.dialog('close');
			// refresh the page to the head revision
			$(location).attr ('href', '<?php print current_url(); ?>');
		}
		else
		{
			this.signin_error.text('<?php print $this->lang->line('MSG_SIGNIN_FAILURE')?>');
		}
	}

	function signinout_on_error (jqXHR, textStatus, errorThrown) 
	{ 
		this.signin_in_progress = false;
		this.signin_ajax = null;
		this.signin_container.dialog('enable');

		var errmsg = textStatus;
		if (errmsg == '' && errorThrown != null) errmsg += " - " + errorThrown;
		if (errmsg == '') errmsg = 'Unknown error';
		this.signin_error.text(errmsg);
	}

	function do_signinout ()
	{
		if (this.signin_in_progress) return;

		if (!!window.FormData)
		{
			// FormData is supported
			var user_name = this.user_name.val();
			var user_pass = this.user_pass.val();
			if (user_name == '' || user_pass == '') return;

			this.signin_error.text ('');
			this.signin_in_progress = true;

			var form_data = new FormData();
			form_data.append ('user_name', user_name);
			form_data.append ('user_pass', user_pass);

			this.signin_container.dialog('disable');

			this.signin_ajax = $.ajax({
				url: signin_url,
				type: 'POST',
				data: form_data,
				mimeType: 'multipart/form-data',
				contentType: false,
				processData: false,
				cache: false,
				context: this,
				success: signinout_on_success,
				error: signinout_on_error
			});
		}
		else
		{
			this.signin_error.text('NOT SUPPORTED');
		}
	}

	function cancel_signinout ()
	{
		if (this.signin_in_progress) 
		{
			this.signin_ajax.abort();
			this.signin_ajax = null;
			this.signin_in_progress = false;
			return;
		}

		this.signin_container.dialog('close');
	}

	function trigger_signinout ()
	{
		var buttons = this.signin_container.dialog("option", "buttons");
		buttons[Object.keys(buttons)[0]](); // trigger the first button
	}

	function signinout_button_on_click ()
	{
	<?php if (isset($login['id']) && $login['id'] != ''): ?>
		if (ready_to_signinout.call(this)) this.signinout_form.submit();
	<?php else: ?>
		this.signin_container.dialog('open');
	<?php endif; ?>
	}

	function get_project_candidates (request, response)
	{
		var term = codepot_string_to_hex(request.term);
		if (this.project_find_ajax != null) { this.project_find_ajax.abort(); }

		this.project_find_ajax = $.ajax({
			url: project_find_url_base + term,
			dataType: "json",
			context: this,
			success: function(data, textStatus, jqXHR) 
			{ 
				this.project_find_ajax = null;
				response (data);  // call the bacllback function for autocompletion
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				// on error, do nothing special
				this.project_find_ajax = null;
			}
		});
	}

	// -------------------------------------------------------------------
	// PUBLIC FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.initWidgets = function ()
	{
		var self = this;

		this.signin_container.dialog ({
			title: '<?php print $this->lang->line('Sign in'); ?>',
			resizable: true,
			autoOpen: false,
			modal: true,
			width: 'auto',
			height: 'auto',
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () { 
					do_signinout.call (self);
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					cancel_signinout.call (self);
				}
			},
			beforeClose: function() { 
				// if signin is in progress, prevent dialog closing
				return !self.signin_in_progress;
			}
		});

		this.user_name.button().bind ('keyup', function(e) {
			if (e.keyCode == 13) trigger_signinout.call (self);
		});

		this.user_pass.button().bind ('keyup', function(e) {
			if (e.keyCode == 13) trigger_signinout.call (self);
		});

		this.signinout_button.button().click (function() {
			signinout_button_on_click.call (self);
			return false;
		});

		// it's not a real button. button() call only for styling purpose
		this.project_to_find.button().autocomplete ({
			minLength: 1, // is this too small?
			delay: 1000,

			source: function (request, response) {
				get_project_candidates.call (self, request, response);
			},

			select: function(event, ui) {
				$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", "/project/home/" + ui.item.id));
				//ui.item.value , ui.item.id ,  this.value
			}
		});
	}

	return App;
})();
/////////////////////////////////////////////////////////////////////////

$(function () {
	var taskbar_app = new TaskbarApp ();
	taskbar_app.initWidgets ();
});

</script> 

<?php
show_taskbar ($this, $login);
?>
