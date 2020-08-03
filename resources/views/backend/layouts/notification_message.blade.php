                    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
                    @if( Session::has('alert-' . $msg) )
                        <div class="alert alert-{{ $msg }} alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                            {!! Session::get('alert-' . $msg) !!}
                        </div>
                    @endif
                    @endforeach