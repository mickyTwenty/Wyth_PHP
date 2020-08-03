@component('mail::message')
# Hello,

A trip which has been shared with you is started. Please find the details of the trip and tracking link.

**Trip Date:** {{ Carbon\Carbon::parse($rideShared->ride->start_time)->format('j M, Y') }}  
**Origin:** {{ $trip->origin_title }}  
**Destination:** {{ $trip->destination_title }}  
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
