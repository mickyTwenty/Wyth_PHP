@if ($record->is_approved == 0)
    <a href="{{ backend_url($moduleProperties['controller'].'/approve/'.$record->id) }}" title="Approve" class="btn btn-xs btn-primary approve">
        <i class="fa fa-check"></i>
    </a>
@else
    <a href="{{ backend_url($moduleProperties['controller'].'/disapprove/'.$record->id) }}" title="Disapprove" class="btn btn-xs btn-primary approve">
        <i class="fa fa-ban"></i>
    </a>
@endif

<a class="btn btn-xs btn-danger delete" type="button" title="Delete" data-toggle="modal" data-target="#deleteModal{{ $record->id }}">
    <i class="fa fa-trash"></i>
</a>

<div id="deleteModal{{ $record->id }}" class="modal fade" role="dialog">
    <div class="modal-dialog">
	    <div class="modal-content">
		    <div class="modal-header">
			    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			    <h4 class="modal-title">Confirm Delete</h4>
		    </div>
		    <div class="modal-body">
			    <p>Are you sure you want to delete this record?</p>
		    </div>
		    <div class="modal-footer">
			    <button type="button" class="btn btn-default btn-flat pull-left" data-dismiss="modal">Close</button>
			    {!! Form::model($record, ['method' => 'delete', 'url' => 'backend/'.$moduleProperties['controller'].'/'.$record->id, 'class' =>'form-inline form-delete']) !!}
			    {!! Form::hidden('id', $record->id) !!}
			    {!! Form::submit('Yes', ['class' => 'btn btn-success btn-flat']) !!}
			    {!! Form::close() !!}
		    </div>
	    </div>
    </div>
</div>
