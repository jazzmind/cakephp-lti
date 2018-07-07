<div class='page-header'>
	<h1> LTI Consumers 
		<?php
		echo "<span class='button-group pull-right'>";
		echo $this->Html->link('<i class="fa fa-plus"></i> add', array('admin' => true, 'plugin' => 'lti', 'controller' => 'consumers', 'action' => 'add'), array('escape' => false, 'class' => 'btn btn-primary btn-sm'));
		echo "&nbsp;";
		echo $this->Html->link('<i class="fa fa-plug"></i> integrations', array('admin' => true, 'plugin' => null,'controller' => 'lti_resources', 'action' => 'index'), array('escape' => false, 'class' => 'btn btn-primary btn-sm'));
		echo "&nbsp;";
		echo $this->Html->link('<i class="fa fa-list"></i> back', array('admin' => true, 'controller' => 'lti_resources', 'action' => 'index'), array('escape' => false, 'class' => 'btn btn-primary btn-sm'));
		echo "</span>";
	?>
	</h1>
</div>
<div class='content-container'>
<div class='row'>

	<div class='col-xs-12'>
		<div class='space-12'></div>
		<div class="table-header">
			<span id='filterLabel'>Showing all results</span>
		</div>
		<div class='table-responsive'>
			<table id="indextbl" class="table table-striped table-bordered table-hover" style="width: 100%">
				<thead>
					<tr>
						<th>Consumer Id</th>
						<th>Name</th>
						<th>Consumer Key</th>
						<th>Consumer GUID</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>
		</div>
	</div>
</div>
</div>
<?php
$this->Html->script('jquery.dataTables.min', array('block' => 'script'));
$this->Html->script('jquery.dataTables.bootstrap', array('block' => 'script'));

$buttons = [
	'view' => true,
	'edit' => true,
	'delete' => true
];

$this->Js->buffer("
	var url = '" . $this->Html->url(array('action' => 'index')) . "/index.json';
	var buttons = " . json_encode($this->DataTable->action_buttons('consumers', '--id--', '--title--', 'lti', $buttons, true)) . ";
	var oTable = init_table(url, buttons);

	function init_table(url, content) {
		var oTable = $('#indextbl').dataTable( {
		    'iDisplayLength': 25,
		    'bProcessing': true,
		    'bServerSide': true,
		    'bAutoWidth': false,
		    'sAjaxSource': url,
		    'aaSorting': [[2,'asc']],
		 	'aoColumns': [
				{ 'bVisible': false, 'bSearchable': false }, // program_id
				{ 'bVisible': true, 'bSearchable': true }, // name
				{  'bVisible': true, 'bSearchable': true }, // key
				{  'bVisible': true, 'bSearchable': false }, // guid
				{ 'bVisible': true , 'bSortable': false, 'sClass': 'td-actions center', 'sWidth': '20%', 'bSearchable': false }
			],

		    'fnCreatedRow': function(nRow, aData, iDataIndex){
		    	// var link = '/admin/lti/consumers/view/' + aData[3];
				// $('td:eq(2)', nRow).html('<a href=\"' + link + '\">' + aData[3] + '</a>');

		    	var neocon = content.replace(/--id--/g, aData[2]);
				neocon = neocon.replace(/--title--/g, aData[1]);
				$('td:eq(3)', nRow).html(neocon);
		    }
		});
		return oTable;
	}

");
