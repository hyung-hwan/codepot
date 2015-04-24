<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />


<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

</head>

<body>

<div class="content" id="login_content">

<!---------------------------------------------------------------------------->

<div class='taskbar'>
	<div class="boxb">
	</div>

	<div class="boxa">
		<ul>
		<li><?php print  anchor ('site/home', $this->lang->line('Home')); ?></li>
		<li><?php print  anchor ('project/catalog', $this->lang->line('Projects')); ?></li>
		</ul>
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
<?php print form_open('main/signin/')?>
	<?php print form_hidden('user_url', set_value ('user_url', $user_url))?>

	<div class="form_input_field">
		<!--
		<?php print form_label($this->lang->line('Username'), 'user_name')?>
		<?php print form_input('user_name', set_value ('user_name', $user_name))?>
		-->

		<?php
		print form_input (
			'user_name', 
			set_value ('user_name', $user_name), 
			"size='30' id='login_user_name' placeholder={$this->lang->line('Username')}"
		);
		?>

		<?php print form_error('user_name')?>
	</div>

	<div class="form_input_field">
		<!--
		<?php print form_label($this->lang->line('Password'), 'user_pass')?>
		<?php print form_password('user_pass')?>
		-->
		<?php
		print form_password (
			'user_pass',
			set_value ('user_pass', $user_pass),
			"size='30' id='login_user_pass' placeholder={$this->lang->line('Password')}"
		);
		?>
		<?php print form_error('user_pass')?>
	</div>

	<div class="form_input_field">
		<!--
		<?php print form_submit('login', $this->lang->line('Sign in'))?>
		-->

		<?php
		print form_submit (
			'login', 
			$this->lang->line('Sign in'), 
			'class="button" id="login_signin_button"'
		);
		?>
	</div>
<?php print form_close();?>
</div> <!-- form_container -->

</div>

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->



</body>
</html>
