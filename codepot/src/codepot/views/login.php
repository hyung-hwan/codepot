<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url_make('/css/common.css')?>" />
</head>

<body>

<div class="content" id="login_content">

<!---------------------------------------------------------------------------->

<div class='taskbar'>
	<div class="boxb">
	</div>

	<div class="boxa">
        <?= anchor ('site/home', $this->lang->line('Home')) ?>
        <?= anchor ('project/catalog', $this->lang->line('Projects')) ?>
	</div>
</div>


<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'login',
			'id' => '',
			'login' => NULL
		),

		'ctxmenuitems' => array ()
	)
);
?>

<div class="mainarea" id="login_mainarea">

<?php if ($message != "") print "<div id='project_create_message' class='form_message'>$message</div>"; ?>

<div class='form_container'>
<?=form_open('main/signin/')?>
	<?=form_hidden('user_url', set_value ('user_url', $user_url))?>

	<div class="form_input_field">
		<!--
		<?=form_label($this->lang->line('Username'), 'user_name')?>
		<?=form_input('user_name', set_value ('user_name', $user_name))?>
		-->

		<?php
		print form_input (
			'user_name', 
			set_value ('user_name', $user_name), 
			"size='30' id='login_user_name' placeholder={$this->lang->line('Username')}"
		);
		?>

		<?=form_error('user_name')?>
	</div>

	<div class="form_input_field">
		<!--
		<?=form_label($this->lang->line('Password'), 'user_pass')?>
		<?=form_password('user_pass')?>
		-->
		<?php
		print form_password (
			'user_pass',
			set_value ('user_pass', $user_pass),
			"size='30' id='login_user_pass' placeholder={$this->lang->line('Password')}"
		);
		?>
		<?=form_error('user_pass')?>
	</div>

	<div class="form_input_field">
		<!--
		<?=form_submit('login', $this->lang->line('Sign in'))?>
		-->

		<?php
		print form_submit (
			'login', 
			$this->lang->line('Sign in'), 
			'class="button" id="login_signin_button"'
		);
		?>
	</div>
<?=form_close();?>
</div> <!-- form_container -->

</div>

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div>

</body>
</html>
