@if ($cancelable)
	<a href="{{ backend_url('trips/cancel/'.$record->id) }}" title="Cancel Trip" class="btn btn-xs btn-danger">
	        <i class="fa fa-ban"></i>
	</a>
@endif
