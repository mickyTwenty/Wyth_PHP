@component('mail::message')
# Hello,

{{ $passenger->full_name }} would like to share the details of their WYTH ride with you.

**Trip Date:** {{ Carbon\Carbon::parse($rideShared->ride->start_time)->format('j M, Y') }}  
**Origin:** {{ $rideShared->ride->origin_title }}  
**Destination:** {{ $rideShared->ride->destination_title }}  
**Driver:** {{ $trip->driver->full_name }}  

@component('mail::button', ['url' => route('track.ride', ['rideShared' => $rideShared->id])])
Track Ride
@endcomponent

@component('mail::center')
www.gowyth.com
@endcomponent

@component('mail::app_links', ['url' => 'http://www.gowyth.com'])
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
