<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
</head>

<body>

<div class="content" id="login_content">

<!---------------------------------------------------------------------------->

<div class='taskbar'>
	<div class="boxb">
	</div>

	<div class="boxa">
        <?= anchor ('user/home', $this->lang->line('Home')) ?>
        <?= anchor ('user/projectlist', $this->lang->line('Projects')) ?>
	</div>
</div>


<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'site' => NULL,
		'project' => NULL,
		'pageid' => '',
		'ctxmenuitems' => array ()
	)
);
?>

<div class="mainarea">

<?php if ($message != "") print "<div id='project_create_message' class='form_message'>$message</div>"; ?>

<?=form_open('main/index/')?>
	<?=form_fieldset('')?>

		<?=form_hidden('user_url', set_value ('user_url', $user_url))?>

		<div class="textfield">
			<?=form_label($this->lang->line('Username'), 'user_name')?>
			<?=form_input('user_name', set_value ('user_name', $user_name))?>
			<?=form_error('user_name')?>
		</div>
	
		<div class="textfield">
			<?=form_label($this->lang->line('Password'), 'user_pass')?>
			<?=form_password('user_pass')?>
			<?=form_error('user_pass')?>
		</div>
	
		<div class="buttons">
			<?=form_submit('login', $this->lang->line('Sign in'))?>
		</div>
	<?=form_fieldset_close()?>

<?=form_close();?>

</div>

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div>

</body>
</html>
