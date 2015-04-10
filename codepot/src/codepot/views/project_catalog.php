<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/project.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript">
function AsciiToHex (x) {
	var r="";
	for(i=0; i<x.length; i++)
	{
		var tmp = x.charCodeAt(i).toString(16);
		if (tmp.length == 1) r += "0";
		r += tmp;
	}
	return r;
}

$(function () { 
	$("#project_catalog_mainarea_search_form").dialog ({
		title: '<?php print $this->lang->line('Search')?>',
		autoOpen: false,
		modal: true,
		width: '80%',
		buttons: { 
			'<?php print $this->lang->line('OK')?>': function () { 
				$(this).dialog('close'); 
				var filter = AsciiToHex($('#project_search_form').serialize());
				var url='<?php print site_url()?>/project/catalog/' + filter;	

				$('body').append('<form id="magic_form" method="get" action="'+url+'"></form>');
				$('#magic_form').submit();
			},
			'<?php print $this->lang->line('Cancel')?>': function () { 
				$(this).dialog('close'); 
			}
		},
		close: function() {}
	}); 


	$("#project_catalog_mainarea_search_button").button().click (
		function () { 
			$('#project_catalog_mainarea_search_form').dialog('open'); 
			return false;
		}
	);
});
</script>

<title><?php print $this->lang->line('Projects')?></title>
</head>

<body>

<div class="content" id="project_catalog_content">

<!---------------------------------------------------------------------------->

<?php $this->load->view ('taskbar'); ?>

<!---------------------------------------------------------------------------->

<?php
$this->load->view (
	'projectbar', 
	array (
		'banner' => $this->lang->line('Projects'),

		'page' => array (
			'type' => '',
			'id' => ''
		),

		'ctxmenuitems' => array (
			array ("project/create", 
			       $this->lang->line('New'),
			       'project_catalog_new')
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_catalog_mainarea">

<div class="infostrip">
<?php printf ($this->lang->line('PROJECT_MSG_TOTAL_NUM_PROJECTS'), $total_num_projects); ?> | 
<a id="project_catalog_mainarea_search_button" href='#'><?php print $this->lang->line('Search')?></a>
</div>

<div id="project_catalog_mainarea_search_form">
	<form id="project_search_form">
		<div>
			<?php print form_label ($this->lang->line('ID'), 'id')
			?>
			<?php print form_input('id',
				set_value('owner', $search->id),
				'id="project_search_id"')
			?>
		</div>

		<div>
			<?php print form_label ($this->lang->line('Name'), 'name')
			?>
			<?php print form_input('name',
				set_value('owner', $search->name),
				'id="project_search_name"')
			?>
		</div>

		<div>
			<?php print form_label ($this->lang->line('Summary'), 'summary')
			?>
			<?php print form_input('summary',
				set_value('summary', $search->summary),
				'id="project_search_summary" size="50"')
			?>
		</div>

	</form>
</div>


<div class="result" id="project_catalog_mainarea_result">
<?php
if (empty($projects))
{
	print $this->lang->line('MSG_NO_PROJECTS_AVAIL');
}
else
{
/*
	print '<table id="project_catalog_mainarea_result_table">';
	print '<tr class="heading">';
	print '<th class="id">' . $this->lang->line('ID') . '</th>';
	print '<th class="name">' . $this->lang->line('Name') . '</th>';
	print '<th class="summary">' . $this->lang->line('Summary') . '</th>';
	print '</tr>';

	$rowclasses = array ("even", "odd");
	$rownum = 0;
	foreach ($projects as $project)
	{
		$rowclass = $rowclasses[++$rownum % 2];
		print "<tr class='{$rowclass}'>";

		print '<td class="id">';
		print anchor ("project/home/{$project->id}", 
			htmlspecialchars($project->id));
		print '</td>';

		print '<td class="name">';
		print htmlspecialchars($project->name);
		print '</td>';

		print '<td class="summary">';
		print htmlspecialchars($project->summary);
		print '</td>';

		print '</tr>';
	}

	print '<tr class="foot">';
	print "<td colspan='3' class='pages'>{$page_links}</td>";
	print '</tr>';

	print '</table>';
*/
	print '<ul>';
	foreach ($projects as $project)
	{
		$cap = "{$project->name} ({$project->id})";
		$anc = anchor ("project/home/{$project->id}", htmlspecialchars($cap));
		$sum = htmlspecialchars ($project->summary);
		print "<li>{$anc} - {$sum}</li>";
	}
	print '</ul>';
	print "<span class='pages'>{$page_links}</span>";
}
?>
</div> <!-- project_catalog_mainarea_result -->

</div> <!-- project_catalog_mainarea -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->


</div> <!-- project_catalog_content -->

</body>
</html>
