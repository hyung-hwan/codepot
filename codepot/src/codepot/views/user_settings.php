<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/user.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

     
<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<title><?php print htmlspecialchars($login['id'])?></title>
</head>

<body>

<div class="content" id="user_settings_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'user',
			'id' => 'issues',
			'user' => $user,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="user_settings_mainarea">

<?php
	if ($message != '') 
		print "<div id='user_settings_mainarea_message' class='form_message'>$message</div>";
?>

<div id="user_settings_mainarea_result" class="result">

<div class="form_container">
<?php print form_open_multipart('user/settings/')?>

	<?php print form_checkbox('code_hide_line_num', 
		'Y', $settings->code_hide_line_num == 'Y')
	?>
	<?php print  $this->lang->line('USER_MSG_HIDE_LINE_NUMBER')?>

	<?php print form_checkbox('code_hide_metadata',
		'Y', $settings->code_hide_metadata == 'Y')
	?>
	<?php print  $this->lang->line('USER_MSG_HIDE_METADATA')?>

	<hr style="height:1px; border:none; background-color:#CCCCCC;" />

	<div class='form_input_field'>
		<?php print form_label($this->lang->line('Icon').': ', 'icon_img_file_name')?>
		<?php
			$extra = 'maxlength="255" size="40"';
			print form_upload('icon_img_file_name', set_value('icon_img_file_name', ''), $extra);
		?>
		<?php print form_error('icon_img_file_name');?>
		(.png, max. 100x100)
	</div>

	<hr style="height:1px; border:none; background-color:#CCCCCC;" />

	<?php print form_label($this->lang->line('Oneliner about me').': ', 'user_summary')?>
	<input type="text" name="user_summary" size="50" value="<?php print addslashes($settings->user_summary); ?>" />

	<hr style="height:1px; border:none; background-color:#CCCCCC;" />

	<div class="buttons">
		<?php print form_submit('settings', $this->lang->line('OK'))?>
	</div>

<?php print form_close();?>
</div>

</div> <!-- user_settings_mainarea_result -->

</div> <!-- user_settings_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- user_settings_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>
</html>
