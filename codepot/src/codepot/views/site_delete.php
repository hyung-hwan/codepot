<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/site.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />


<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />


<title><?php print htmlspecialchars($site->name)?></title>
</head>

<body>

<div class="content" id="site_delete_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->
<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => $this->lang->line('Administration'),

		'page' => array (
			'type' => 'site',
			'id' => 'catalog',
			'site' => $site,
		),

		'ctxmenuitems' => array ()
	)
);
?>
<!---------------------------------------------------------------------------->

<div class="mainarea">

<?php if ($message != "") print "<div id='site_create_message' class='form_message'>$message</div>"; ?>

<div class="form_container">
<?php print form_open("site/delete/{$site->id}")?>

	<div>
		<div>
			<?php print form_checkbox('site_confirm', 'yes', set_checkbox('site_confirm', $site_confirm))?>
			<?php print $this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?php print htmlspecialchars($site->name)?> (<?php print $site->id?>)
			<?php print form_error('site_confirm')?>
		</div>
	</div>

	<div>
		<?php print form_hidden('site_id', set_value('site_id', $site->id))?>
		<?php print form_hidden('site_name', set_value('site_name', $site->name))?>
	</div>

	<div>
		<?php print form_submit('site', $this->lang->line('Delete'))?>
	</div>

<?php print form_close();?>
</div>

</div> <!-- mainarea -->

<div class='footer-pusher'></div> <!-- for sticky footer -->

</div>  <!-- site_delete_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</body>

</html>
