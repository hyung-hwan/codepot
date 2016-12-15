<html>

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/project.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/vis.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/vis.min.css')?>" />

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#project_map_alert').html(outputMsg).dialog({
		title: titleMsg,
		resizable: true,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			"<?php print $this->lang->line('OK')?>": function () {
				$(this).dialog("close");
			}
		}
	});
}


var revision_network = null;
var revision_network_data = null;

function resize_window()
{
	var footer = $("#codepot_footer");
	var mainarea = $("#project_map_mainarea");
	var code = $("#project_user_relation_graph");

	if (revision_network !== null)
	{
		// make it low so that the footer gets places at the right place.
		revision_network.setSize (300, 300);
		//revision_network.redraw();
	}

	var ioff = mainarea.offset();
	var foff = footer.offset();

	if (revision_network !== null)
	{
		revision_network.setSize (footer.innerWidth() - 10, foff.top - ioff.top - 10);
		revision_network.redraw();
		revision_network.fit();
	}
}

function show_project_user_relation_graph (response)
{
	var data = $.parseJSON(response);
	if (data == null)
	{
		show_alert ('Invalid data received', "<?php print $this->lang->line('Error')?>");
	}
	else if (data.nodes.length <= 0)
	{
		show_alert ('No data to show', "<?php print $this->lang->line('Info')?>");
	}
	else
	{
		var options = {
			autoResize: false,
			height: '300px',
			width: '300px',
			clickToUse: false,
			layout: {
				hierarchical: {
					enabled: true,
					//levelSeparation: 150,
					//nodeSpacing: 200,
					//treeSpacing: 300,
					//direction: 'LR', //'LR' 'UD', 'DU', 'RL'
					sortMethod: 'hubsize' // 'directed' 
				}
			},
			edges: {
				smooth: {
				    type: 'cubicBezier',
				    forceDirection: 'horizontal', // 'vertical',
				    roundness: 0.4
				}
			},
			physics: {
				enabled: true
			}
		};

		var i, j;

		j = data.nodes.length;
		for (i = 0; i < j; i++)
		{
			if (data.nodes[i]._type == 'project')
			{
				data.nodes[i].shape = 'box';
				//data.nodes[i].label = '<a href="xxx">' + codepot_htmlspecialchars(data.nodes[i].label) + '</a>';
			}
			else
			{
				data.nodes[i].shape = 'ellipse';
				data.nodes[i].color =  { border: '#FF7777', background: '#DACACA' };
			}
		}

		j = data.edges.length;
		for (i = 0; i < j; i++)
		{
			data.edges[i].length = 60;
			data.edges[i].width = 1;
			data.edges[i].arrows = 'to';
			data.edges[i].font = { color: 'red' };
		}

		if (revision_network === null)
		{
			revision_network = new vis.Network(document.getElementById('project_user_relation_graph'), data, options);
			revision_network_data = data;

			revision_network.on ('click', function (props) {
				if (props.nodes.length > 0)
				{
					var i, j;
					for (i = 0, j = revision_network_data.nodes.length; i < j; i++)
					{
						if (revision_network_data.nodes[i].id == props.nodes[0])
						{
							if (revision_network_data.nodes[i]._type == 'project')
							{
								$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '/project/home/' + revision_network_data.nodes[i].label));
							}
							else
							{
								$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '/user/home/' + codepot_string_to_hex(revision_network_data.nodes[i].label)));
							}
						}
					}
				}
			});
		}
		else
		{
			revision_network.setData (data);
		}
	}

	$(window).resize(resize_window);
	resize_window ();
}



$(function () { 
	var ajax_req = $.ajax ({
		url: codepot_merge_path (
			"<?php print site_url(); ?>", 
			"/graph/enjson_project_user_relation_graph"),
		context: document.body,
		success: show_project_user_relation_graph,
		error: function (xhr, ajaxOptions, thrownError) {
			show_alert (xhr.status + ' ' + thrownError, "<?php print $this->lang->line('Error')?>");
			//$("#code_folder_revision_graph_button").button("enable");
			//$("#code_folder_revision_graph_spin" ).removeClass ("fa-cog fa-spin");
		}
	});
});
</script>

<title><?php print $this->lang->line('Projects')?></title>
</head>

<body>

<div class="content" id="project_map_content">

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
			array ("project/create", '<i class="fa fa-plus"></i> ' . $this->lang->line('New'), 'project_map_new'),
			array ("project/catalog", $this->lang->line('Directory'), 'project_map_catalog')
		)
	)
); 
?>

<!---------------------------------------------------------------------------->

<div class="mainarea" id="project_map_mainarea">

<div class="result" id="project_map_result">

<div id="project_user_relation_graph">
</div>

</div> <!-- project_map_result -->

<div id='code_folder_alert'></div>

</div> <!-- project_map_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- project_map_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->



</body>
</html>
