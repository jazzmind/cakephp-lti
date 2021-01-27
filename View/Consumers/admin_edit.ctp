<div class="page-header">
	<h1>
		<?=__('Edit LTI Consumer')?>
 		<span class='pull-right'>
 			<?= $this->Html->link('<i class="icon-list"></i> Go back', ['action' => 'index'], [
 				'class' => 'btn btn-primary btn-sm tooltip-info',
 				'data-rel' => 'tooltip',
 				'data-placement' => 'bottom',
 				'title' => 'Go Back',
 				'escape' => false
 			]);
 			?>
 		</span>
	</h1>
</div>
<div class='content-container'>
	<div class="row">
		<div class="col-xs-12">
			<?= $this->Form->create('Consumer', [
				'inputDefaults' => [
					'div' => 'form-group',
					'label' => ['class' => 'col-sm-2 control-label'],
					'wrapInput' => 'col-xs-12 col-sm-5',
					'class' => 'form-control'
				],
				'class' => 'form-horizontal'
			]); ?>
			<div class='form-group'>
				<label class='col-sm-2 control-label'>Enabled</label>
				<div class='col-xs-12 col-sm-5'>
					<label>
						<?= $this->Form->checkbox('Consumer.enabled', ['class' => 'ace ace-switch ace-switch-2']); ?>
						<span class="lbl"></span>
					</label>
				</div>
			</div>
			<!-- <div class='form-group'>
				<label class='col-sm-2 control-label'>Protected</label>
				<div class='col-xs-12 col-sm-5'>
					<label>
						<?= $this->Form->checkbox('Consumer.protect', ['class' => 'ace ace-switch ace-switch-2']); ?>
						<span class="lbl"></span>
					</label>
				</div>
			</div> -->
			<?= $this->Form->input('Consumer.name'); ?>
			<?= $this->Form->input('Consumer.consumer_name'); ?>
			<?= $this->Form->input('Consumer.secret'); ?>
			<?= $this->Form->input('Consumer.css_path'); ?>
			<?= $this->Form->input('Consumer.enable_from', [
				'type' => 'text',
				'class' => 'form-control datetime-picker',
				'data-date-format' => 'DD/MM/YYYY hh:mm a',
				'wrapInput' => 'col-sm-2',
				'beforeInput' => '<div class="input-group">',
				'afterInput' => '<span class="input-group-addon"><i class="ace-icon fa fa-calendar bigger-120"></i></span></div>'
			]); ?>
			<?= $this->Form->input('Consumer.enable_until', [
				'type' => 'text',
				'class' => 'form-control datetime-picker',
				'data-date-format' => 'DD/MM/YYYY hh:mm a',
				'wrapInput' => 'col-sm-2',
				'beforeInput' => '<div class="input-group">',
				'afterInput' => '<span class="input-group-addon"><i class="ace-icon fa fa-calendar bigger-120"></i></span></div>'
			]); ?>
			<div class="form-actions clearfix">
				<div class="col-md-12 col-md-offset-2">
					<?= $this->Form->button("<i class='icon-ok bigger-110'></i> Save", ['type' => 'submit', 'class' => 'btn btn-info', 'escape' => false]); ?>
					<?= $this->Html->link('<i class="icon-undo bigger-110"></i> Cancel', ['action' => 'index'], ['class' => 'btn', 'escape' => false]);?>
				</div>
			</div>
			<?= $this->Form->end(); ?>
		</div>
	</div>
</div>

<?php
$this->Html->css('date-time/bootstrap-datetimepicker', array('block' => 'css'));

$this->Html->script("date-time/moment.min", array('block' => 'script'));
$this->Html->script("date-time/moment-timezone.min", array('block' => 'script'));
$this->Html->script("date-time/timezone/all", array('block' => 'script'));

$this->Html->script("date-time/bootstrap-datetimepicker.min", array('block' => 'script'));

$this->Js->buffer("
	$('.datetime-picker').datetimepicker({stepping: 15, useCurrent: false, sideBySide: true});
	$('#ConsumerEnableFrom').val($('#ConsumerEnableFrom').attr('value'));
	$('#ConsumerEnableUntil').val($('#ConsumerEnableUntil').attr('value'));
");
?>
