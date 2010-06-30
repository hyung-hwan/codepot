<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/common.css" />
<link type="text/css" rel="stylesheet" href="<?=base_url()?>/css/site.css" />
<title><?=htmlspecialchars($site->name)?></title>
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

<?=form_open("site/delete/{$site->id}")?>
	<?=form_fieldset()?>
		<div>
			<div>
				<?=form_checkbox('site_confirm', 'yes', set_checkbox('site_confirm', $site_confirm))?>
				<?=$this->lang->line('MSG_SURE_TO_DELETE_THIS')?> - <?=htmlspecialchars($site->name)?> (<?=$site->id?>)
				<?=form_error('site_confirm')?>
			</div>
		</div>

		<div>
			<?=form_hidden('site_id', set_value('site_id', $site->id))?>
			<?=form_hidden('site_name', set_value('site_name', $site->name))?>
		</div>

		<div>
			<?=form_submit('site', $this->lang->line('Delete'))?>
		</div>

	<?=form_fieldset_close()?>
<?=form_close();?>

</div>

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->

</div>  <!-- site_delete_content -->

</body>

</html>
