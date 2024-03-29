<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">

<script type="text/javascript" src="<?php print base_url_make('/js/codepot.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/common.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/code.css')?>" />
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/font-awesome.min.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/creole.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/showdown.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/prettify/prettify.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-css.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lisp.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-lua.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-sql.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/prettify/lang-vb.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?php print base_url_make('/js/jquery-ui.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jquery-ui.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/jqueryui-editable.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/jqueryui-editable.css')?>" />

<script type="text/javascript" src="<?php print base_url_make('/js/chart.min.js')?>"></script>

<script type="text/javascript" src="<?php print base_url_make('/js/vis.min.js')?>"></script>
<link type="text/css" rel="stylesheet" href="<?php print base_url_make('/css/vis.min.css')?>" />

<?php
	$file_count = count($file['content']);

	$hex_headpath = $this->converter->AsciiToHex($headpath);

	if ($revision <= 0)
	{
		$revreq = '';
		$revreqroot = '';

		$history_path = "/code/history/{$project->id}/{$hex_headpath}";
	}
	else
	{
		$revreq = "/{$revision}";
		$revreqroot = '/' . $this->converter->AsciiToHex('.') . $revreq;

		if ($hex_headpath == '') $revtrailer = $revreqroot;
		else $revtrailer = "/{$hex_headpath}{$revreq}";
		$history_path = "/code/history/{$project->id}{$revtrailer}";
	}

	$history_anchor_text = '<i class="fa fa-history"></i> ' . $this->lang->line('History');
	$download_anchor_text = '<i class="fa fa-download"></i> ' . $this->lang->line('Download');
	$diff_anchor_text = '<i class="fa fa-server"></i> ' . $this->lang->line('Difference');
	$fulldiff_anchor_text = '<i class="fa fa-tasks"></i> ' . $this->lang->line('Full Difference');
	$blame_anchor_text = '<i class="fa fa-bomb"></i> ' . $this->lang->line('Blame'); 

	$show_search = (CODEPOT_SIGNIN_FOR_CODE_SEARCH === FALSE || (isset($login['id']) && $login['id'] != ''));
?>

<script type="text/javascript">

function show_alert (outputMsg, titleMsg) 
{
	$('#code_folder_mainarea_alert').html(outputMsg).dialog({
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

var GraphApp = (function ()
{
	// -------------------------------------------------------------------
	// CONSTRUCTOR
	// -------------------------------------------------------------------
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		//if (this.constructor != App)
		//{
		//	return new App (top_container, graph_container, graph_canvas, graph_button, graph_spin, graph_title);
		//}

		this.top_container = top_container;
		this.graph_container = graph_container;
		this.graph_msgdiv = graph_msgdiv;
		this.graph_canvas = graph_canvas;
		this.graph_button = graph_button;
		this.graph_spin = graph_spin;
		this.graph_ajax = null;
		this.graph_url = graph_url;
		this.graph_title = graph_title;

		this.graph_msgdiv.hide();
		this.graph_msgdiv.css ({'background-color': 'red', 'color': 'white', 'padding': '1em', 'float': 'left'});
		return this;
	}

	// -------------------------------------------------------------------
	// PRIVATE FUNCTIONS
	// -------------------------------------------------------------------
	function on_graph_success (response, textStatus, jqXHR)
	{
		var data;
		try { data = $.parseJSON(response); } 
		catch (e) { data = null; }

		if (data == null)
		{
			this.showMessage ('Invalid data received');
		}
		else
		{
			this.renderGraph (data);

			this.graph_container.dialog("option", "height", (this.top_container.height() * 90 / 100));
			this.graph_container.dialog("option", "width", (this.top_container.width() * 90 / 100));
			this.graph_container.dialog ({
				position: {
					my: "center center",
					at: "center center",
					of: this.top_container
				}
			});

			this.resizeGraph ();
		}

		this.graph_button.button("enable");
		this.graph_spin.removeClass ("fa-cog fa-spin");
		this.graph_ajax = null;
	}

	function on_graph_error (jqXHR, textStatus, errorThrown) 
	{
		this.showMessage (jqXHR.status + ' ' + errorThrown);
		this.graph_button.button("enable");
		this.graph_spin.removeClass ("fa-cog fa-spin");
		this.graph_ajax = null;
	}

	function open_graph()
	{
		this.graph_button.button ("disable");
		this.graph_spin.addClass ("fa-cog fa-spin");
		this.graph_container.dialog ("open");

		if (this.graph_ajax != null) this.graph_ajax.abort();
		this.graph_ajax = $.ajax ({
			url: this.graph_url,
			context: this,
			success: on_graph_success,
			error: on_graph_error
		});
	}

	// -------------------------------------------------------------------
	// PUBLIC FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.initWidgets = function () 
	{
		var self = this;

		this.graph_container.dialog ({
			title: this.graph_title,
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('Close')?>': function () {
					if (self.graph_ajax != null) return;
					self.graph_container.dialog('close');
				}
			},

			beforeClose: function() {
				return self.graph_ajax == null;
			},
		});

		this.graph_container.on ("dialogresize", function (evt, ui) {
			self.resizeGraph ();
		});

		this.graph_container.on ("dialogclose", function (evt, ui) {
			self.closeGraph ();
		});

		this.graph_button.button().click (function ()
		{
			open_graph.call (self);
			return false;
		});
	};


	App.prototype.showMessage = function (msg)
	{
		this.graph_msgdiv.show();
		this.graph_msgdiv.text (msg);
		this.graph_msgdiv.position ({
			my: "center",
			at: "center",
			of: this.graph_container
		});
	}

	App.prototype.clearMessage = function()
	{
		this.graph_msgdiv.hide();
	}

	// -------------------------------------------------------------------
	// VIRTUAL FUNCTIONS
	// -------------------------------------------------------------------
	App.prototype.renderGraph = function (json) 
	{
		/* SHOULD BE IMPLEMENTED BY INHERITER */
	}

	App.prototype.resizeGraph = function ()
	{
		/* SHOULD BE IMPLEMENTED BY INHERITER */
	}

	App.prototype.closeGraph = function ()
	{
		/* SHOULD BE IMPLEMENTED BY INHERITER */
	}

	return App;
})();

var RevGraphApp = (function ()
{
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		GraphApp.call (this, top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title);
		this.revision_network = null;
		return this;
	}
	App.prototype = Object.create (GraphApp.prototype);
	App.prototype.constructor = App;

	App.prototype.renderGraph = function (data)
	{
		if (data.nodes.length <= 0)
		{
			this.showMessage ('No data to show');
			return;
		}

		this.clearMessage ();
		var options = {
			autoResize: false,
			height: '400px',
			width: '90%',
			clickToUse: false /*,
			layout: {
				hierarchical: {
					enabled: true,

					//levelSeparation: 150,
					//nodeSpacing: 200,
					//treeSpacing: 300,
					//direction: 'LR' //'LR' 'UD', 'DU', 'RL'
					//sortMethod: 'directed' // 'hubsize'
				}
			},

			edges: {
				smooth: {
				    //type: 'cubicBezier',
				    //forceDirection: 'horizontal', // 'vertical',
				    roundness: 0.4
				}
			},
			physics: {
				enabled: true
			}*/
		};

		var i, j;

		j = data.nodes.length;

		for (i = 0; i < j; i++)
		{
			data.nodes[i].labelHighlightBold = false;
			data.nodes[i].shape = 'box';
			if (data.nodes[i]._type == '')
			{
				// no other nodes reache this node 
				data.nodes[i].color = '#553322';
				data.nodes[i].font = { color: 'white' };
			}
			else if (data.nodes[i]._type.indexOf('D') >= 0)
			{
				data.nodes[i].color = '#AA3344';
				data.nodes[i].font = { color: 'white' };
				data.nodes[i].label += '\n<<DELETED>>';
			}
			else if (data.nodes[i]._type.indexOf('A') >= 0)
			{
				data.nodes[i].color = '#227722';
				data.nodes[i].font = { color: 'white' };
				data.nodes[i].label += '\n<<NEW>>';
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

		if (this.revision_network === null)
		{
			this.revision_network = new vis.Network(this.graph_canvas[0], data, options);
		}
		else
		{
			this.revision_network.setData (data);
		}
	}

	App.prototype.resizeGraph = function ()
	{
		if (this.revision_network != null)
		{
			this.revision_network.setSize (this.graph_container.width(), this.graph_container.height());
			this.revision_network.redraw();
			this.revision_network.fit();
		}
	}

	return App;
})();


var LocLangApp = (function ()
{
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		GraphApp.call (this, top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title);
		this.plot_dataset = null;
		this.plot_options = null;
		return this;
	}

	App.prototype = Object.create (GraphApp.prototype);
	App.prototype.constructor = App;

	App.prototype.renderGraph = function (loc)
	{
		var self = this;

		this.clearMessage ();

		var labels = [];

		var blank = [];
		var comment = [];
		var code = [];
		for (var key in loc) 
		{
			labels.push (key);
			blank.push (loc[key][1]);
			comment.push (loc[key][2]);
			code.push (loc[key][3]);
		}

		this.plot_dataset = {
			labels: labels,
			datasets: [
				{ 
					label: "<?php print $this->lang->line('Blank')?>",
					data: blank
				},
				{ 
					label: "<?php print $this->lang->line('Comment')?>",
					data: comment
				},
				{ 
					label: "<?php print $this->lang->line('Code')?>",
					data: code
				},
			]
		};


		this.plot_options = {
			responsive: true,
			maintainAspectRatio: false,
			scales: {
				x: { 
					grid: { display: false },
					stacked: true
				},
				y: {
					beginAtZero: true,
					grid: { display: true }
				}
			}
		};

		this.chart = new Chart(this.graph_canvas[0].getContext('2d'), {
			type: 'bar',
			data: this.plot_dataset,
			options: this.plot_options
		});
	}

	App.prototype.resizeGraph = function ()
	{
		if (this.plot_dataset != null)
		{
			this.graph_canvas.width (this.graph_container.width() - 5);
			this.graph_canvas.height (this.graph_container.height() - 10);
		}
	}

	App.prototype.closeGraph = function ()
	{
		if (this.chart != null)
		{
			this.chart.destroy();
			this.chart = null;
		}
	}

	return App;
})();


var LocFileApp = (function ()
{
	function App (top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title)
	{
		GraphApp.call (this, top_container, graph_container, graph_msgdiv, graph_canvas, graph_button, graph_spin, graph_url, graph_title);
		this.plot_dataset = null;
		this.plot_options = null;
		this.chart = null;
		return this;
	}

	App.prototype = Object.create (GraphApp.prototype);
	App.prototype.constructor = App;

	App.prototype.renderGraph = function (loc)
	{
		var self = this;

		this.clearMessage ();

		//var gen_color = function() {
		//	var letters = '0123456789ABCDEF'.split('');
		//	var color = '#';
		//	for (var i = 0; i < 6; i++) color += letters[Math.floor(Math.random() * 16)];
		//	return color;
		//};

		var labels = [];
		var dataset_lines = [];
		var dataset_bytes = [];
		var bgcolors = [];

		var add_items = function(items)
		{
			for (var key in items) {
				var item = items[key];
				if ('children' in item)
				{
					// directory item.
					add_items (item.children);	
				}
				else
				{
					// plain file item
					var lines = item.lines;
					if (lines == null) lines = 0;
					var size = item.size;
					if (size == null) lines = 0;
					labels.push (item.name);
					dataset_lines.push (item.lines == null? 0: item.lines);
					dataset_bytes.push (item.bytes == null? 0: item.bytes);
					//bgcolors.push(gen_color());
				}
			}
		};
		add_items (loc.children);

		this.plot_dataset = {
			labels: labels,
			datasets: [
				{ 
					label: 'Lines',
					data: dataset_lines
					//backgroundColor: bgcolors
				}/*,
				{ 
					label: 'Bytes',
					data: dataset_bytes
				}*/
			]
		};
		this.plot_options = {
			responsive: true,
			maintainAspectRatio: false,
			scales: {
				x: { 
					grid: { display: false },
					stacked: true
				},
				y: {
					beginAtZero: true,
					grid: { display: true }
				}
			}
		};

		this.chart = new Chart(this.graph_canvas[0].getContext('2d'), {
			type: 'bar',
			data: this.plot_dataset,
			options: this.plot_options
		});
	}

	App.prototype.resizeGraph = function ()
	{
		if (this.plot_dataset != null)
		{
			this.graph_canvas.width (this.graph_container.width() - 5);
			this.graph_canvas.height (this.graph_container.height() - 10);
		}
	}

	App.prototype.closeGraph = function ()
	{
		if (this.chart != null)
		{
			this.chart.destroy();
			this.chart = null;
		}
	}
	return App;
})();

function render_readme()
{
	<?php
	// if the readme file name ends with '.wc', perform wiki formatting and pretty printing
	if (strlen($readme_text) > 0 && substr_compare($readme_file, '.wc', -3) === 0):
	?>
	creole_render_wiki (
		"code_folder_readme_text",
		"code_folder_readme",
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/show/<?php print $project->id?>/"),
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/attachment0/<?php print $project->id?>/"),
		false
	);
	prettyPrint();
	<?php
	// if the readme file name ends with '.md', perform markdown formatting
	elseif (strlen($readme_text) > 0 && substr_compare($readme_file, '.md', -3) === 0):
	?>
	showdown_render_wiki (
		"code_folder_readme_text",
		"code_folder_readme",
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/show/<?php print $project->id?>/"),
		codepot_merge_path("<?php print site_url(); ?>", "/wiki/attachment0/<?php print $project->id?>/"),
		false
	);
	prettyPrint();
	<?php endif; ?>
}

var new_item_no = 0;
var import_in_progress = false;
var delete_in_progress = false;
var rename_in_progress = false;
var rename_last_input = {};

function get_new_item_html(no, type, name)
{
	return codepot_sprintf (
		'<li><input type="%s" id="code_folder_mainarea_new_item_%s_%d" name="code_folder_new_item_%s_%d" %s /></li>',  
		type, name, no, name, no, ((type == 'file')? 'multiple=""': '')
	);
}

$(function () {

<?php if (isset($login['id']) && $login['id'] != ''): ?>

	new_item_no = 0;
	$('#code_folder_mainarea_new_file_list').append (get_new_item_html(new_item_no, 'file', 'file'));
	$('#code_folder_mainarea_new_dir_list').append (get_new_item_html(new_item_no, 'text', 'dir'));
	$('#code_folder_mainarea_new_empfile_list').append (get_new_item_html(new_item_no, 'text', 'empfile'));

	$("#code_folder_mainarea_new_form_tabs").tabs ();

	$('#code_folder_mainarea_new_form_div').dialog (
		{
			title: '<?php print $this->lang->line('New');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'More': function () {
					if (import_in_progress) return;

					++new_item_no;
					$('#code_folder_mainarea_new_file_list').append (get_new_item_html(new_item_no, 'file', 'file'));
					$('#code_folder_mainarea_new_dir_list').append (get_new_item_html(new_item_no, 'text', 'dir'));
					$('#code_folder_mainarea_new_empfile_list').append (get_new_item_html(new_item_no, 'text', 'empfile'));
				},
				'<?php print $this->lang->line('OK')?>': function () {
					if (import_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						import_in_progress = true;

						var form_data = new FormData();

						form_data.append ('code_new_message', $('#code_folder_mainarea_new_message').val());
						if ($('#code_folder_mainarea_new_item_unzip').is(':checked'))
							form_data.append ('code_new_item_unzip', $('#code_folder_mainarea_new_item_unzip').val());
						else
							form_data.append ('code_new_item_unzip', '');

						var f_no = 0, d_no = 0, ef_no = 0;
						for (var i = 0; i <= new_item_no; i++)
						{
							f = $('#code_folder_mainarea_new_item_file_' + i).get(0);
							if (f != null)
							{
								for (var n = 0; n < f.files.length; n++)
								{
									if (f.files[n] != null) 
									{
										form_data.append ('code_new_item_file_' + f_no, f.files[n]);
										f_no++;
									}
								}
							}

							var d = $('#code_folder_mainarea_new_item_dir_' + i).val();
							if (d != null && d != '') 
							{
								form_data.append ('code_new_item_dir_' + d_no, d);
								d_no++;
							}

							var d = $('#code_folder_mainarea_new_item_empfile_' + i).val();
							if (d != null && d != '') 
							{
								form_data.append ('code_new_item_empfile_' + ef_no, d);
								ef_no++;
							}
						}
						var x_no = f_no;
						if (d_no > x_no) x_no = d_no;
						if (ef_no > x_no) x_no = ef_no;

						form_data.append ('code_new_item_count', x_no);

						$('#code_folder_mainarea_new_form_div').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_import/{$project->id}/{$hex_headpath}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								import_in_progress = false;
								$('#code_folder_mainarea_new_form_div').dialog('enable');
								$('#code_folder_mainarea_new_form_div').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/file/{$project->id}/{$hex_headpath}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								import_in_progress = false;
								$('#code_folder_mainarea_new_form_div').dialog('enable');
								$('#code_folder_mainarea_new_form_div').dialog('close');

								var errmsg = '';
								if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
								if (errmsg == '' && textStatus != null) errmsg = textStatus;
								if (errmsg == '') errmsg = 'Unknown error';
								show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (import_in_progress) return;
					$('#code_folder_mainarea_new_form_div').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !import_in_progress;
			}
		}
	);

	$('#code_folder_mainarea_delete_form_div').dialog (
		{
			title: '<?php print $this->lang->line('Delete');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (delete_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						delete_in_progress = true;

						var form_data = new FormData();

						form_data.append ('code_delete_message', $('#code_folder_mainarea_delete_message').val());
						var xi = 0;
						for (var i = 1; i <= <?php print $file_count; ?>; i++)
						{
							var f = $('#code_folder_result_table_file_selector_' + i);
							if (f != null && f.is(':checked'))
							{
								form_data.append ('code_delete_file_' + xi, f.val());
								xi++;
							}
						}
						form_data.append ('code_delete_file_count', xi);

						$('#code_folder_mainarea_delete_form_div').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_delete/{$project->id}/{$hex_headpath}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								delete_in_progress = false;
								$('#code_folder_mainarea_delete_form_div').dialog('enable');
								$('#code_folder_mainarea_delete_form_div').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/file/{$project->id}/{$hex_headpath}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								delete_in_progress = false;
								$('#code_folder_mainarea_delete_form_div').dialog('enable');
								$('#code_folder_mainarea_delete_form_div').dialog('close');

								var errmsg = '';
								if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
								if (errmsg == '' && textStatus != null) errmsg = textStatus;
								if (errmsg == '') errmsg = 'Unknown error';
								show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}
				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (delete_in_progress) return;
					$('#code_folder_mainarea_delete_form_div').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				return !delete_in_progress;
			}
		}
	);

	$('#code_folder_mainarea_rename_form').dialog (
		{
			title: '<?php print $this->lang->line('rename');?>',
			resizable: true,
			autoOpen: false,
			width: 'auto',
			height: 'auto',
			modal: true,
			buttons: {
				'<?php print $this->lang->line('OK')?>': function () {
					if (rename_in_progress) return;

					if (!!window.FormData)
					{
						// FormData is supported
						rename_in_progress = true;

						var form_data = new FormData();

						form_data.append ('code_rename_message', $('#code_folder_mainarea_rename_message').val());
						var xi = 0;
						for (var i = 1; i <= <?php print $file_count; ?>; i++)
						{
							var f = $('#code_folder_result_table_file_selector_' + i);
							if (f != null && f.is(':checked'))
							{
								form_data.append ('code_rename_file_old_' + xi, f.val());

								var fx = $('#code_folder_mainarea_rename_file_' + xi);
								var fxv = fx != null? fx.val(): '';
								form_data.append ('code_rename_file_new_' + xi, fxv);

								xi++;
							}
						}
						form_data.append ('code_rename_file_count', xi);

						$('#code_folder_mainarea_rename_form').dialog('disable');
						$.ajax({
							url: codepot_merge_path('<?php print site_url() ?>', '<?php print "/code/xhr_rename/{$project->id}/{$hex_headpath}"; ?>'),
							type: 'POST',
							data: form_data,
							mimeType: 'multipart/form-data',
							contentType: false,
							processData: false,
							cache: false,

							success: function (data, textStatus, jqXHR) { 
								rename_in_progress = false;
								$('#code_folder_mainarea_rename_form').dialog('enable');
								$('#code_folder_mainarea_rename_form').dialog('close');
								if (data == 'ok') 
								{
									// refresh the page to the head revision
									$(location).attr ('href', codepot_merge_path('<?php print site_url(); ?>', '<?php print "/code/file/{$project->id}/{$hex_headpath}"; ?>'));
								}
								else
								{
									show_alert ('<pre>' + codepot_htmlspecialchars(data) + '</pre>', "<?php print $this->lang->line('Error')?>");
								}
							},

							error: function (jqXHR, textStatus, errorThrown) { 
								rename_in_progress = false;
								$('#code_folder_mainarea_rename_form').dialog('enable');
								$('#code_folder_mainarea_rename_form').dialog('close');

								var errmsg = '';
								if (errmsg == '' && errorThrown != null) errmsg = errorThrown;
								if (errmsg == '' && textStatus != null) errmsg = textStatus;
								if (errmsg == '') errmsg = 'Unknown error';
								show_alert ('Failed - ' + errmsg, "<?php print $this->lang->line('Error')?>");
							}
						});
					}
					else
					{
						show_alert ('<pre>NOT SUPPORTED</pre>', "<?php print $this->lang->line('Error')?>");
					}

				},
				'<?php print $this->lang->line('Cancel')?>': function () {
					if (rename_in_progress) return;
					$('#code_folder_mainarea_rename_form').dialog('close');
				}

			},

			beforeClose: function() { 
				// if importing is in progress, prevent dialog closing
				rename_last_input = {};
				var xi = 0;
				for (var i = 1; i <= <?php print $file_count; ?>; i++)
				{
					var f = $('#code_folder_result_table_file_selector_' + i);
					if (f != null && f.is(':checked'))
					{
						var fx = $('#code_folder_mainarea_rename_file_' + xi);
						var fxv = fx != null? fx.val(): '';
						rename_last_input[f.val()] = fxv;

						xi++;
					}
				}

				return !rename_in_progress;
			}
		}
	);

	$('#code_folder_mainarea_new_button').button().click (function() {
		$('#code_folder_mainarea_new_form_div').dialog('open');
		return false; // prevent the default behavior
	});

	$('#code_folder_mainarea_delete_button').button().click (function() {
		var xi = 0;
		for (var i = 1; i <= <?php print $file_count; ?>; i++)
		{
			var f = $('#code_folder_result_table_file_selector_' + i);
			if (f != null && f.is(':checked')) xi++;
		}
		$('#code_folder_mainarea_delete_form_div').dialog ('option', 'title', 
			codepot_sprintf ("<?php print addslashes($this->lang->line('CODE_FMT_DELETE_X_SELECTED_FILES')) ?>", xi)
		);
		$('#code_folder_mainarea_delete_form_div').dialog('open');

		return false; // prevent the default behavior
	});

	$('#code_folder_mainarea_rename_button').button().click (function() {
		var xi = 0;

		$('#code_folder_mainarea_rename_file_table').empty();
		for (var i = 1; i <= <?php print $file_count; ?>; i++)
		{
			var f = $('#code_folder_result_table_file_selector_' + i);
			if (f != null && f.is(':checked')) 
			{
				var li = rename_last_input[f.val()];
				if (li == null) li = '';
				$('#code_folder_mainarea_rename_file_table').append (
					codepot_sprintf ('<tr><td>%s</td><td><input type="text" id="code_folder_mainarea_rename_file_%d" value="%s"/></td></tr>', 
						codepot_htmlspecialchars(f.val()), xi, codepot_addslashes(li))
				);
				xi++;
			}
		}

		$('#code_folder_mainarea_rename_form').dialog ('option', 'title', 
			codepot_sprintf ("<?php print addslashes($this->lang->line('CODE_FMT_RENAME_X_SELECTED_FILES')) ?>", xi)
		);
		$('#code_folder_mainarea_rename_form').dialog('open');

		return false; // prevent the default behavior
	});
<?php endif; ?>

<?php if (isset($login['id']) && $login['id'] != ''): ?>
	$('#code_folder_result_table_select_all').button().click (function() {
		$('.file_selector').prop('checked', $('#code_folder_result_table_select_all').is(':checked'));
	});
<?php endif; ?>

	$('#code_folder_metadata').accordion({
		collapsible: true,
		heightStyle: "content"

	});

	<?php if ($revision > 0 && $revision < $next_revision): ?>
		$("#code_folder_headrev_button").button().click (function() {
			$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/file/{$project->id}/${hex_headpath}"; ?>'));
			return false;
		});
	<?php endif; ?>

	$("#code_folder_history_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print $history_path; ?>'));
		return false;
	});
	$("#code_folder_download_button").button().click (function() {
		$(location).attr ('href', codepot_merge_path("<?php print site_url(); ?>", '<?php print "/code/fetch/{$project->id}/${hex_headpath}{$revreq}"; ?>'));
		return false;
	});

	var rev_graph_app = new RevGraphApp (
		$(window), 
		$("#code_folder_revision_graph_container"),
		$("#code_folder_revision_graph_error"),
		$("#code_folder_revision_graph"),
		$("#code_folder_revision_graph_button"),
		$("#code_folder_revision_graph_spin"),
		codepot_merge_path ("<?php print site_url(); ?>", "/graph/enjson_revision_graph/<?php print $project->id; ?>/<?php print $hex_headpath;?><?php print $revreq?>"),
		"<?php print $this->lang->line('Revision')?>"
	);
	rev_graph_app.initWidgets ();

	var loc_by_lang_app = new LocLangApp (
		$(window), 
		$("#code_folder_loc_by_lang_container"),
		$("#code_folder_loc_by_lang_error"),
		$("#code_folder_loc_by_lang"),
		$("#code_folder_loc_by_lang_button"),
		$("#code_folder_loc_by_lang_spin"),
		codepot_merge_path ("<?php print site_url(); ?>", "/graph/enjson_loc_by_lang/<?php print $project->id; ?>/<?php print $hex_headpath;?><?php print $revreq?>"),
		"LOC-<?php print $this->lang->line('Language')?>"
	);
	loc_by_lang_app.initWidgets ();

	var loc_by_file_app = new LocFileApp (
		$(window),
		$("#code_folder_loc_by_file_container"),
		$("#code_folder_loc_by_file_error"),
		$("#code_folder_loc_by_file"),
		$("#code_folder_loc_by_file_button"),
		$("#code_folder_loc_by_file_spin"),
		codepot_merge_path ("<?php print site_url(); ?>", "/graph/enjson_loc_by_file/<?php print $project->id; ?>/<?php print $hex_headpath;?><?php print $revreq?>"),
		"LOC-<?php print $this->lang->line('File')?>"
	);
	loc_by_file_app.initWidgets ();

<?php if ($show_search): ?>
	$('#code_search_invertedly').button();
	$('#code_search_case_insensitively').button();
	$('#code_search_recursively').button();
	$('#code_search_in_name').button();
	$('#code_search_is_regex').button();
	$('.code_search_option').tooltip({
		position: {
			my: "left top",
			at: "right-5 bottom-5",
			collision: "none"
		}
	});

	$('#code_search_wildcard').text($('input[name=search_wildcard_pattern]').val());

	$('#code_search_wildcard').editable({
		type: 'text', 
		title: '<?php print $this->lang->line('CODE_SEARCH_WILDCARD') ?>',
		placement: 'bottom',
		success: function(response, newValue) {
			$('input[name=search_wildcard_pattern]').val(newValue);
		}
	});

	$('#code_folder_search').dialog({
		title: '<?php print $this->lang->line('Search'); ?>',
		resizable: true,
		autoOpen: false,
		modal: true,
		width: 'auto',
		height: 'auto',
		buttons: {
			'<?php print $this->lang->line('OK')?>': function () {
				if ($.trim($('#code_search_string').val()) != "")
				{
					$('#code_search_string_icon').addClass("fa-cog fa-spin");
					$('#code_folder_search').dialog ('disable');
					$('#code_search_form').submit ();
				}
			},
			'<?php print $this->lang->line('Cancel')?>': function () {
				$('#code_folder_search').dialog('close');
			}
		}
	});

	$("#code_folder_search_button").button().click (function () {
		$('#code_folder_search').dialog('open');
		return false;
	});
<?php endif; ?>

	render_readme ();
});

</script>

<title><?php 
	if ($headpath == '')
		printf ('%s', htmlspecialchars($project->name));
	else
		printf ('%s - %s', htmlspecialchars($project->name), htmlspecialchars($headpath));
?></title>
</head>

<body>

<div class="content" id="code_folder_content">

<!-- ================================================================== -->

<?php $this->load->view ('taskbar'); ?>

<!-- ================================================================== -->

<?php
$this->load->view (
	'projectbar',
	array (
		'banner' => NULL,

		'page' => array (
			'type' => 'project',
			'id' => 'code',
			'project' => $project,
		),

		'ctxmenuitems' => array ()
	)
);
?>

<!-- ================================================================== -->

<div class="mainarea" id="code_folder_mainarea">

<div class="codepot-title-band" id="code_folder_title_band">

	<div class="title">
	<?php
		// print the main anchor for the root folder. 
		// let the anchor text be the project name.
		print anchor (
			"code/file/{$project->id}{$revreqroot}", 
			htmlspecialchars($project->id));

		// explode non-root folder parts to anchors
		$exps = explode ('/', $headpath);
		$expsize = count($exps);
		$par = '';
		for ($i = 1; $i < $expsize; $i++)
		{
			print '/';
			$par .= '/' . $exps[$i];
			$xpar = $this->converter->AsciiToHex ($par);
			print anchor (
				"code/file/{$project->id}/{$xpar}{$revreq}",
				htmlspecialchars($exps[$i]));
		}

		if ($headpath != $file['fullpath'])
		{
			print ' - ';
			print htmlspecialchars ($file['fullpath']);
		}
	?>
	</div>

	<div class="actions">
		<?php
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$prev_revision}", '<i class="fa fa-arrow-circle-left"></i>');
		print ' ';

		// anchor to the revision history at the root directory
		print anchor (
			//"code/revision/{$project->id}/!/{$file['created_rev']}", 
			"code/revision/{$project->id}/${hex_headpath}/{$file['created_rev']}", 
			sprintf("%s %s", $this->lang->line('Revision'), $file['created_rev'])
		);

		if (!empty($file['created_tag']))
		{
			print ' ';
			printf ('<span class="left_arrow_indicator">%s</span>', htmlspecialchars($file['created_tag']));
		}

		print ' ';
		print anchor ("code/file/{$project->id}/${hex_headpath}/{$next_revision}", '<i class="fa fa-arrow-circle-right"></i>');

		if (isset($login['id']) && $login['id'] != '')
		{
			print ' ';
			printf ('<a id="code_folder_mainarea_new_button" href="#">%s</a>', $this->lang->line('New'));
			printf ('<a id="code_folder_mainarea_delete_button" href="#">%s</a>', $this->lang->line('Delete'));
			printf ('<a id="code_folder_mainarea_rename_button" href="#">%s</a>', $this->lang->line('Rename'));
		}

		?>
	</div>
	<div style="clear: both;"></div>
</div>

<div id='code_folder_metadata' class='collapsible-box'>
	<div id='code_folder_metadata_header' class='collapsible-box-header'>
		<?php
		print '<div class="metadata-committer">';
		$user_icon_url = codepot_merge_path (site_url(), '/user/icon/' . $this->converter->AsciiToHex($file['last_author']));
		print "<img src='{$user_icon_url}' class='metadata-committer-icon' />";
		print htmlspecialchars ($file['last_author']);
		print '</div>';

		print '<div class="metadata-menu">';
		if ($revision > 0 && $revision < $next_revision)
		{
			$head_revision_text = '<i class="fa fa-exclamation-triangle" style="color:#CC2222"></i> ' . $this->lang->line('Head');
			print anchor ('#', $head_revision_text, 'id="code_folder_headrev_button"');
		}

		print anchor ('#', $history_anchor_text, 'id="code_folder_history_button"');
		//print anchor ('#', $download_anchor_text, 'id="code_folder_download_button"');
		print anchor ("code/fetch/{$project->id}/${hex_headpath}{$revreq}", $download_anchor_text, 'id="code_folder_download_button"');

		print '<a id="code_folder_loc_by_lang_button" href="#">';
		print '<i id="code_folder_loc_by_lang_spin" class="fa"></i>LOC-';
		print $this->lang->line('Language');
		print '</a>';

		print '<a id="code_folder_loc_by_file_button" href="#">';
		print '<i id="code_folder_loc_by_file_spin" class="fa"></i>LOC-';
		print $this->lang->line('File'); 
		print '</a>';

		print '<a id="code_folder_revision_graph_button" href="#">';
		print '<i id="code_folder_revision_graph_spin" class="fa"></i>';
		print $this->lang->line('CODE_REVISION_GRAPH'); 
		print '</a>';

		if ($show_search)
		{
			print '<a id="code_folder_search_button" href="#">';
			print $this->lang->line('Search'); 
			print '</a>';
		}

		print '</div>';

		print '<div class="metadata-commit-date">';
		printf ('[%s] ', $file['created_rev']);
		print codepot_unixtimetodispdate(strtotime($file['last_changed_date']));
		print '</div>';
		?>
		<div style='clear: both;'></div>
	</div>

	<div id='code_folder_metadata_body' class='codepot-metadata-collapsible-body'>
		<div class='codepot-plain-text-view'>
			<?php
				$transformed_message = htmlspecialchars($file['logmsg']);
				// handle [[#RXXX]]
				$transformed_message = preg_replace (
					"/\[\[(#R([[:digit:]]+))\]\]/",
					'[[' . anchor ("/code/revision/{$project->id}/!./\${2}", "\${1}", "class='codepot-hashed-revision-number'") . ']]',
					$transformed_message
				);
			?>
			<pre id="code_folder_metadata_text"><?php print $transformed_message; ?></pre>
		</div>

		<?php
		if (array_key_exists('properties', $file) && !is_null($file['properties']) && count($file['properties']) > 0)
		{
			print '<div><ul id="code_folder_property_list">';
			foreach ($file['properties'] as $pn => $pv)
			{
				print '<li>';
				print htmlspecialchars($pn);
				if ($pv != '')
				{
					print ' - ';
					print htmlspecialchars($pv);
				}
				print '</li>';
			}
			print '</ul></div>';
		}
		?>
	</div>
</div>

<div id="code_folder_graph" class="graph">
	<div id="code_folder_loc_by_lang_container">
		<!-- <div id="code_folder_loc_by_lang"></div> -->
		<canvas id="code_folder_loc_by_lang"></canvas>
		<div id="code_folder_loc_by_lang_error"></div>
	</div>

	<div id="code_folder_loc_by_file_container">
		<!-- <div id="code_folder_loc_by_file"></div> -->
		<canvas id="code_folder_loc_by_file"></canvas>
		<div id="code_folder_loc_by_file_error"></div>
	</div>

	<div id="code_folder_revision_graph_container">
		<div id="code_folder_revision_graph"></div>
		<div id="code_folder_revision_graph_error"></div>
	</div>
</div>

<div id="code_folder_search">
	<?php
	print form_open("code/search/{$project->id}/", 'id="code_search_form"');
	if ($show_search)
	{
		print form_hidden('search_folder', set_value('search_folder', $file['fullpath']));
		print form_hidden('search_revision', set_value('search_revision', $revision));

		print '<div id="code_folder_search_string_div">';
		print form_input(array(
			'name' => 'search_string', 
			'value' => set_value('search_string', ''), 
			'id' =>'code_search_string',
			'placeholder' => $this->lang->line('CODE_SEARCH_STRING')
		));
		print '<i id="code_search_string_icon" class="fa"></i> ';
		print '</div>';

		print '<div id="code_folder_search_option_div">';
		print form_checkbox(array(
			'name'    => 'search_invertedly', 
			'id'      => 'code_search_invertedly',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE/*,
			'title'   => $this->lang->line('CODE_SEARCH_INVERTEDLY')*/
		));
		print form_label('v', 'code_search_invertedly', 
			array('class'=>'code_search_option',
			      'id'=>'code_search_invertedly_label',
			      'title' => $this->lang->line('CODE_SEARCH_INVERTEDLY'))
		);

		print form_checkbox(array(
			'name'    => 'search_case_insensitively', 
			'id'      => 'code_search_case_insensitively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE/*,
			'title'   => $this->lang->line('CODE_SEARCH_CASE_INSENSITIVELY')*/
		));
		print form_label('i', 'code_search_case_insensitively', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_case_insensitively_label',
			      'title'   => $this->lang->line('CODE_SEARCH_CASE_INSENSITIVELY'))
		);

		print form_checkbox(array(
			'name'    => 'search_recursively', 
			'id'      => 'code_search_recursively',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => TRUE/*,
			'title'   => $this->lang->line('CODE_SEARCH_RECURSIVELY')*/
		));
		print form_label('r', 'code_search_recursively', 
			array('class' => 'code_search_option',
			      'id' => 'code_search_recursively_label',
			      'title' => $this->lang->line('CODE_SEARCH_RECURSIVELY'))
		);

		print form_checkbox(array(
			'name'    => 'search_in_name', 
			'id'      => 'code_search_in_name',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE/*,
			'title'   => $this->lang->line('CODE_SEARCH_IN_NAME')*/
		));
		print form_label('n', 'code_search_in_name',
			array('class' => 'code_search_option',
			      'id' => 'code_search_in_name_label',
			      'title' => $this->lang->line('CODE_SEARCH_IN_NAME'))
		);

		print form_checkbox(array(
			'name'    => 'search_is_regex', 
			'id'      => 'code_search_is_regex',
			'class'   => 'code_search_option',
			'value'   => 'Y',
			'checked' => FALSE/*,
			'title'   => $this->lang->line('CODE_SEARCH_IS_REGEX')*/
		));
		print form_label('x', 'code_search_is_regex',
			array('class'=>'code_search_option',
			      'id'=>'code_search_is_regex_label',
			      'title' => $this->lang->line('CODE_SEARCH_IS_REGEX') )
		);

		print '<a id="code_search_wildcard" href="#"></a>';
		print form_hidden('search_wildcard_pattern', set_value('search_wildcard_pattern', $wildcard_pattern));

		print '</div>';
	} 
	print form_close();
	?>
</div>

<div id="code_folder_result" class="codepot-relative-container-view" >
	<?php
	function comp_files ($a, $b)
	{
		if ($a['type'] == $b['type'])
		{
			return strcasecmp ($a['name'], $b['name']);
		}

		return ($a['type'] == 'dir')? -1: 1;
	}

	if ($file_count <= 0)
	{
		 print $this->lang->line('MSG_NO_CODE_AVAIL');
	}
	else 
	{
		usort ($file['content'], 'comp_files');

		print '<table id="code_folder_result_table" class="codepot-fit-width-table codepot-spacious-table">';
		print '<tr class="heading">';
		if (isset($login['id']) && $login['id'] != '')
		{
			print '<th align="middle"><input type="checkbox" id="code_folder_result_table_select_all" /><label for="code_folder_result_table_select_all"><i class="fa fa-check"></i></label></th>';
		}
		print '<th>' . $this->lang->line('Name') . '</th>';
		print '<th>' . $this->lang->line('Revision') . '</th>';
		print '<th>' . $this->lang->line('Size') . '</th>';
		print '<th>' . $this->lang->line('Committer') . '</th>';
		print '<th>' . $this->lang->line('Date') . '</th>';
		print '<th>' . $this->lang->line('Blame') . '</th>';
		print '<th>' . $this->lang->line('Difference') . '</th>';
		print '<th>' . $this->lang->line('Full Difference') . '</th>';
		print '</tr>';

		$rowclasses = array ('even', 'odd');
		$rownum = 0;
		foreach ($file['content'] as $f)
		{
			//$fullpath = $headpath . '/' . $f['name'];
			$fullpath = $file['fullpath'] . '/' . $f['name'];

			$rowclass = $rowclasses[++$rownum % 2];
			if ($f['type'] === 'dir')
			{
				// directory 
				$hexpath = $this->converter->AsciiToHex($fullpath);
				print "<tr class='{$rowclass}'>";
				if (isset($login['id']) && $login['id'] != '')
				{
					print '<td align="middle">';
					printf ('<input type="checkbox" name="code_folder_file_%d" value="%s" class="file_selector" id="code_folder_result_table_file_selector_%d" />', $rownum, htmlspecialchars($f['name']), $rownum);
					print '</td>';
				}
				print '<td>';
				print '<i class="fa fa-folder-o"></i> ';
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td></td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><tt>';
				print codepot_unixtimetodispdate ($f['time_t'], 'Y-m-d');
				print '</tt></td>';
				print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				print '</tr>';
			}
			else
			{
				// file
				$hexpath = $this->converter->AsciiToHex($fullpath);
				$executable_class  = array_key_exists('executable', $f)? 'executable': '';
				print "<tr class='{$rowclass} {$executable_class}'>";
				if (isset($login['id']) && $login['id'] != '')
				{
					print '<td align="middle">';
					printf ('<input type="checkbox" name="code_folder_file_%d", value="%s" class="file_selector" id="code_folder_result_table_file_selector_%d" />', $rownum, htmlspecialchars($f['name']), $rownum);
					print '</td>';
				}
				print '<td>';
				$fa_type = codepot_get_fa_file_type ($f['name']);
				print "<i class='fa fa-{$fa_type}-o'></i> ";
				print anchor (
					"code/file/{$project->id}/{$hexpath}{$revreq}",
					htmlspecialchars($f['name']));
				print '</td>';
				print '<td>';
				print $f['created_rev'];
				print '</td>';
				print '<td>';
				print $f['size'];
				print '</td>';
				print '<td>';
				print htmlspecialchars($f['last_author']);
				print '</td>';
				print '<td><tt>';
				print codepot_unixtimetodispdate ($f['time_t'], 'Y-m-d');
				print '</tt></td>';

				print '<td>';
				print anchor ("code/blame/{$project->id}/{$hexpath}{$revreq}", $blame_anchor_text);
				print '</td>';
				print '<td>';
				print anchor ("code/diff/{$project->id}/{$hexpath}{$revreq}", $diff_anchor_text);
				print '</td>';
				print '<td>';
				print anchor ("code/fulldiff/{$project->id}/{$hexpath}{$revreq}", $fulldiff_anchor_text);
				print '</td>';
				print '</tr>';
			}
		}
		print '</table>';
	}
	?>
</div> <!-- code_folder_result -->

<?php
if (strlen($readme_text) > 0)
{
	print '<div id="code_folder_readme" class="codepot-styled-text-view">';
	print '<pre id="code_folder_readme_text">';
	print htmlspecialchars($readme_text);
	print '</pre>';
	print '</div>';
}
?>

<?php if (isset($login['id']) && $login['id'] != ''): ?>

<div id="code_folder_mainarea_new_form_div">
	<div><?php print $this->lang->line('Message'); ?>:</div>
	<div><textarea type='textarea' id='code_folder_mainarea_new_message' name='code_folder_new_message' style='width:100%;'></textarea></div>
	
	<div id="code_folder_mainarea_new_form_tabs" style="width:100%;">
		<ul>
			<li><a href="#code_folder_mainarea_new_file_div"><?php print $this->lang->line('Upload'); ?></a></li>
			<li><a href="#code_folder_mainarea_new_dir_div"><?php print $this->lang->line('Directory'); ?></a></li>
			<li><a href="#code_folder_mainarea_new_empfile_div"><?php print $this->lang->line('File'); ?></a></li>
		</ul>
		<div id="code_folder_mainarea_new_file_div">
			<div><input type='checkbox' id='code_folder_mainarea_new_item_unzip' name='code_folder_new_item_unzip' value='yes'/><?php print $this->lang->line('Unzip a zip file'); ?></div>
			<div><ul id='code_folder_mainarea_new_file_list'></ul></div>
		</div>
		<div id="code_folder_mainarea_new_dir_div">
			<div><ul id='code_folder_mainarea_new_dir_list'></ul></div>
		</div>
		<div id="code_folder_mainarea_new_empfile_div">
			<div><ul id='code_folder_mainarea_new_empfile_list'></ul></div>
		</div>
	</div>
</div>

<div id="code_folder_mainarea_delete_form_div">
	<div><?php print $this->lang->line('Message'); ?>:</div>
	<div><textarea type='textarea' id='code_folder_mainarea_delete_message' name='code_folder_delete_message' style='width:100%;' ></textarea></div>
</div>

<div id="code_folder_mainarea_rename_form">
	<div><?php print $this->lang->line('Message'); ?>:</div>
	<div><textarea type='textarea' id='code_folder_mainarea_rename_message' name='code_folder_rename_message' style='width:100%;' ></textarea></div>
	<div id="code_folder_mainarea_rename_file_div">
		<div><table id='code_folder_mainarea_rename_file_table'></table></div>
	</div>
</div>

<?php endif; ?>

<div id='code_folder_mainarea_alert'></div>

</div> <!-- code_folder_mainarea -->

<div class='codepot-footer-pusher'></div> <!-- for sticky footer -->

</div> <!--  code_folder_content -->

<!-- ================================================================== -->

<?php $this->load->view ('footer'); ?>

<!-- ================================================================== -->

</body>

</html>

