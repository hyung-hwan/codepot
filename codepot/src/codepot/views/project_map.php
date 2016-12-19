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
var GraphApp = (function() 
{
	// -------------------------------------------------------------------
	// PRIVATE DATA
	// -------------------------------------------------------------------
	var graph_options = 
	{
		autoResize: false,
		height: '300px',
		width: '300px',
		clickToUse: false,
		layout: 
		{
			hierarchical: 
			{
				enabled: false,
			}
		},
		physics:
		{
			enabled: true
		}
	};
	var edge_font = { color: 'red' };
	var edge_color = { color:'#5577CC', highlight:'pink', hover: '#5577CC', opacity:1.0 };

	// -------------------------------------------------------------------
	// CONSTRUCTOR
	// -------------------------------------------------------------------
	function App (graph_container_id, refresh_button_id, refresh_spin_id, refresh_progress_id, filter_id, header_id, footer_id, alert_container_id)
	{
		if (this.constructor != App)
		{
			return new App(refresh_button_id, refresh_spin_id);
		}

		this.graph_container = $('#' + graph_container_id);
		this.refresh_button = $('#' + refresh_button_id);
		this.refresh_spin = $('#' + refresh_spin_id);
		this.refresh_progress = $('#' + refresh_progress_id);
		this.filter = $('#' + filter_id);
		this.header = $('#' + header_id);
		this.footer = $('#' + footer_id);
		this.alert_container = $('#' + alert_container_id);
		this.url_base = codepot_merge_path ("<?php print site_url(); ?>", "/graph/enjson_project_members");
		this.ajax_req = null;

		this.graph = null;
		this.data = null;

		this.filter_value_used = "";
		this.initial_uids = [];

		return this;
	}

	// -------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// -------------------------------------------------------------------
	function show_alert (outputMsg, titleMsg) 
	{
		this.alert_container.html(outputMsg).dialog({
			title: titleMsg,
			resizable: true,
			modal: true,
			width: 'auto',
			height: 'auto',
			buttons: {
				"<?php print $this->lang->line('OK')?>": function () 
				{
					$(this).dialog("close");
				}
			}
		});
	}

	function convert_data (data)
	{
		var ids = {};
		var gd = { nodes: [], edges: [] };
		var seq = 0;

		for (var prid in data)
		{
			gd.nodes.push ({ __type: "p", id: seq, label: prid, shape: "box" });
			ids[prid] = seq;
			seq++;

			for (var i = 0; i < data[prid].length; i++)
			{
				var uid = data[prid][i];

				if (!(uid in ids))
				{
					var image_url = codepot_merge_path('<?php print site_url(); ?>', "/user/icon/" + codepot_string_to_hex(uid));
					gd.nodes.push ({ __type: "u", id: seq, label: uid, shape: "image", image: image_url });
					ids[uid] = seq;
					seq++;
				}

				gd.edges.push ({ from: ids[prid], to: ids[uid], width: 1, font: edge_font, color: edge_color });
			}
		}

		return gd;
	}

	function get_all_unique_uids (data, existing)
	{
		var uids = {};

		if (existing != null)
		{
			for (var i = 0; i < existing.length; i++) uids[existing[i]] = 1;
		}
		for (var prid in data)
		{
			for (var i = 0; i < data[prid].length; i++)
			{
				uids[data[prid][i]] = 1;
			}
		}
		return Object.keys(uids);
	}

	function handle_double_click (nodeid)
	{
		// TODO: store node-id to name mapping and use it
		//       instead of iterating in a loop.
		for (var i = 0, j = this.data.nodes.length; i < j; i++)
		{
			if (this.data.nodes[i].id == nodeid)
			{
				if (this.data.nodes[i].__type == "p")
				{
					$(location).attr ("href", codepot_merge_path('<?php print site_url(); ?>', "/project/home/" + this.data.nodes[i].label));
				}
				else
				{
					$(location).attr ("href", codepot_merge_path('<?php print site_url(); ?>', "/user/home/" + codepot_string_to_hex(this.data.nodes[i].label)));
				}
				break;
			}
		}
	}

	function show_graph (response)
	{
		var data;
		try { data = $.parseJSON(response); } // TODO: for jquery 3.0 or later, JSON.parse() should be used.
		catch (e) { data = null; }

		if (data == null)
		{
			show_alert.call (this, 'Invalid data received', "<?php print $this->lang->line('Error')?>");
		}
		else if (data.length <= 0)
		{
			show_alert.call (this, 'No data to show', "<?php print $this->lang->line('Error')?>");
		}
		else
		{
			if (this.filter_value_used == '') this.initial_uids.length = 0;
			this.initial_uids = get_all_unique_uids.call (this, data, this.initial_uids);
			this.filter.autocomplete('option', 'source', this.initial_uids);

			this.data = convert_data.call (this, data);
			if (this.graph === null)
			{
				var self = this;

				this.graph = new vis.Network(this.graph_container[0], this.data, graph_options);
				this.graph.on ('doubleClick', function (props) 
				{
					if (props.nodes.length > 0) handle_double_click.call (self, props.nodes[0]);
				});

				this.graph.on ('startStabilizing', function () 
				{
					self.refresh_button.button ("disable");
				});
				this.graph.on ('stabilizationProgress', function (params) 
				{
					var prog = params.iterations / params.total;
					self.refresh_progress.text (Math.round(prog*100)+'%');
				});
				this.graph.on ('stabilizationIterationsDone', function () 
				{
					self.refresh_progress.text ("");
					self.refresh_button.button ("enable");
				});
				this.graph.on ('stabilized', function (params) 
				{
					self.refresh_progress.text ("");
					self.refresh_button.button ("enable");
				});
			}
			else
			{
				this.graph.setData (this.data);
			}

			this.resize ();
		}

		this.refresh_button.button("enable");
		this.refresh_spin.removeClass ("fa-cog fa-spin");
		this.ajax_req = null;
	}

	function handle_error (xhr, textStatus, thrownError) 
	{
		show_alert.call (this, xhr.status + ' ' + thrownError, "<?php print $this->lang->line('Error')?>");
		this.refresh_button.button("enable");
		this.refresh_spin.removeClass ("fa-cog fa-spin");
		this.ajax_req = null;
	}

	// -------------------------------------------------------------------
	// PUBLIC FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.initWidgets = function ()
	{
		var self = this;

		this.refresh_button.button().click (function () 
		{
			self.refresh (self.filter.val().trim());
			return false;
		});

		// not a real button. button() for styling only
		this.filter.button().bind ('keyup', function(e) 
		{
			if (e.keyCode == 13) self.triggerRefresh ();
		});

		this.filter.autocomplete  ({
			minLength: 1, // is this too small?
			delay: 500,
			source: [], // to be set upon data load
			select: function(event, ui) { self.refresh (ui.item.value); }
		});
	};

	App.prototype.refresh = function (filter)
	{
		var url = this.url_base;
		if(filter.length > 0) url += "/" + codepot_string_to_hex(filter);
		this.filter_value_used = filter;
		this.refresh_button.button("disable");
		this.refresh_spin.addClass ("fa-cog fa-spin");

		if (this.ajax_req !== null) this.ajax_req.abort();
		this.ajax_req = $.ajax ({url: url, context: this, success: show_graph, error: handle_error });
	};

	App.prototype.triggerRefresh = function ()
	{
		this.refresh_button.trigger ('click');
	};

	App.prototype.resize = function ()
	{
		if (this.graph !== null)
		{
			// make it low so that the footer gets places at the right place.
			this.graph.setSize (300, 300);

			var hoff = this.header.offset();
			var foff = this.footer.offset();

			hoff.top += this.header.outerHeight() + 5;

			this.graph.setSize (this.footer.innerWidth() - 10, foff.top - hoff.top - 10);
			this.graph.redraw();
			this.graph.fit();
		}
	};

	return App;
})();

/* ---------------------------------------------------------------------- */

$(function () 
{
	var graph_app = new GraphApp (
		'project_map_graph', 'project_map_refresh_button', 
		'project_map_refresh_spin', 'project_map_progress', 
		'project_map_filter', 'project_map_title_band',
		'codepot_footer', 'project_map_alert');

	graph_app.initWidgets ();
	$(window).resize(function () { graph_app.resize(); });
	graph_app.triggerRefresh ();
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

<div class="codepot-title-band" id="project_map_title_band">
	<div class="title"><?php print $this->lang->line('Graph');?></div>

	<div class="actions">
		<span id="project_map_progress"></span>
		<input type="text" id="project_map_filter" placeholder="<?php print $this->lang->line('Username'); ?>" />
		<a id="project_map_refresh_button" href='#'><i id="project_map_refresh_spin" class="fa"></i><?php print $this->lang->line('Refresh')?></a>
	</div>

	<div style='clear: both'></div>
</div>

<div class="result" id="project_map_result">

<div id="project_map_graph">
</div>

</div> <!-- project_map_result -->

<div id='project_map_alert'></div>

</div> <!-- project_map_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!-- project_map_content -->

<!---------------------------------------------------------------------------->

<?php $this->load->view ('footer'); ?>

<!---------------------------------------------------------------------------->



</body>
</html>
