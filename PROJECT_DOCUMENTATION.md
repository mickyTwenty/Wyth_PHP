Database Schema Document
========================

`trips`
- It is a parent table. which hold abstract information for trip.
- Entry on this table indicates its a public/request trip

`trip_rides`
- Its a child table of `trips`
- A trip can either have one-way or two-way. Two-way trip has 2 entries on this table and `is_roundtrip` flag on parent table.

`trip_members`
- Members on this table will indicate that they are members of this trip.
- Members means (they have reserved seats or confirmed booking)
- Driver has rights to kick reserved members only.

`trip_invoices`
- Invoice will be generated for each user separately because of different offers and coupon used during booking.
- Invoice will be generated upon confirmation for the ride.
- 'total_gross' before coupon if any. 'total_net' is received amount which is charged to customers after applying all discounts.

To Change Searching Radius
==========================
- Values should be in meters
- Change value in: config/constants.php
- Change value in: app/Models/TripRideRoute.php

Work-around for Search Trips
============================

Matching Criteria
=================
- Matching co-ordinates
- Trips which start date is above form time-now
- Seats Available (At-least `x` seats available to integer value)
- Trip lie under time-range
- As per preferences set
- Search by rating (1+)

Update Driver Rating
====================
- When someone give rating to driver, calculate all ratings and save it to `user_meta` table.
- Search for a ride with at-least `x` rating is being calculated by above value.

Sort Criteria
================
- Friends in trip

Offers/Book-Now
===============
- Book-Now is as simple af. Passenger will be confirmed when they hit book-now service.
> When passenger either requesting a ride alone or with friends or making offer on existing ride.
|-- We create entries to `trip_offers` for invited passengers only.
|-- If leader-passenger hit booknow then we book this passenger else offer flow will be same as described below.

> Passenger requesting for a ride alone
|-- Entry on trips (with is_request=1), `trip_rides`, `trip_ride_routes`, `trip_request_members` and `trip_ride_meta` (optional)
|-- It'll be searchable by driver ref[3], making an offer, driver will provide seats_total. Upon driver accept make sure seats_total filled.
|-- Offer may be given by passenger again to driver, driver accept the offer and final acceptance will be on passenger end
|-- Passenger accept the offer, following should happen.
    - Assign driver with `user_id`
    - Mark trip as public
    - Attach self-passenger to that trip and reduce seat
    - Create polygon routes
    - Make payment behalf of user.

> Passenger requesting for a ride with friends (passengers only)
|-- At the time of service hit, API will make sure that if driver is also invited then `seats_total` param must be there and not empty.
|-- Entry on trips (with is_request=1), `trip_rides`, `trip_ride_routes`, `trip_request_members` and `trip_ride_meta` (optional)
|-- It'll be searchable by driver ref[3], making an offer, driver will provide seats_total. Upon driver accept make sure seats_total filled. It should be greater or equal than passengers in group.
|-- Offer may be given by passenger again to driver, driver accept the offer and final acceptance will be on passenger end
|-- If any single passenger accept the offer, following should happen.
    - Assign driver with `user_id`
    - Mark trip as public
    - Create polygon routes.
    - Attach all passengers to that trip unique `group_id`
    - Reduce number of seats.
|-- After all grouped-passengers accept driver offers. Following should happen.
    - Payment will occurred.

> Passenger requesting for a ride with friends (passenger with 1 driver only)
|-- At the time of service hit, API will make sure that if driver is also invited then `seats_total` param must be there and not empty.
|-- Entry on trips (with is_request=0), `trip_rides`, `trip_ride_routes`, `trip_request_members`, `trip_ride_polygon` and `trip_ride_meta` (optional) and driver will be assigned directly to that trip and trip will be public.
|-- We create entries on `trip_members` so that seats can mark as reserved
|-- Also we directly create entry on `trip_ride_offers` with `proposed_amount` = 0 and unique `group_id` (passengers only), because somehow we need to give listing for offers for both passenger and driver, in that case they can see offers.
|-- There should be a way to identify the driver_id among invitess.
|-- Any of them can initiate the first offer and final acceptance will be on passenger end
|-- If any passenger accept the offer, following should happen.
    - Just mark current as confirm passenger
    - (removed by me, already handled above) Assign driver with `user_id`
    - (removed by me, already handled above) Mark trip as public
    - (removed by me, already handled above) Create polygon routes if not created.
    - (removed by me, already handled above) Attach all passengers to that trip unique `group_id`
    - (removed by me, already handled above) Reduce number of seats.
|-- After all grouped-passengers accept driver offers. Following should happen.
    - Payment will occurred.

> Passenger searching for a ride which is already exist in DB created by driver.
|-- Passenger initiating offer with friends invitation (group travel) (assume 2 invitees), they'll hit ref[1] service with (time_range, amount,
|   bags, is_roundtrip)
|-- Create entry in `trip_ride_offers` with all friends `to_user_id` with `passenger` type. (3 entries 2+1 in this scanario)
|-- Driver will receive one notification and listing in offers it'll show n+1 entries
|-- If driver, accepts any one's offer among them. Following should happen.
    - Reconfirm of acceptance from passenger's end
    - First check for available seats for all the invited members + leader
    - All of the grouped-passengers will be added to reserved list with a unique `group_id`
    - Seats available will be reduced accordingly.
    - Change time-range of the trip if applicable.
|-- After all grouped-passengers accept driver offers. Following should happen.
    - Payment will occurred.

> Driver creating a ride.
|-- Entry on trips (with is_request=1), `trip_rides`, `trip_ride_routes`, `trip_ride_polygon` and `trip_ride_meta` (optional)
|-- Assign driver with `user_id`
|-- Create polygon routes if not created.
|-- END.

> Driver creating a ride with friends (passengers only)
|-- Entry on trips (with is_request=0), `trip_rides`, `trip_ride_routes`, `trip_ride_polygon` and `trip_ride_meta` (optional)
|-- Trip will be public.
|-- Seats will be reduced accordingly.
|-- We directly create entry on `trip_ride_offers` with `proposed_amount` = 0 and unique `group_id` (passengers only), because somehow we need to give listing for offers for both passenger and driver, in that case they can see offers.
|-- Any of them can initiate the first offer and final acceptance will be on passenger end
|-- If passenger accept the offer, following should happen.
    - Attach this passenger to that trip with unique `group_id` (here `group_id` is useless but we will save)
    - Payment will occurred.

> Driver searching for a request which is already exist in DB created by passenger.
|-- Driver initiating offer, he'll hit ref[2] service with (seats_total, amount)
|-- Create entry in `trip_ride_offers` with offer to all passengers which is invited on this request by passenger-leader (assume 2+1)
|-- All passengers will received offer notification and listing 1 offer by driver on each user interface.
|-- If any passenger accepts offer. Following should happen.
    - First check for available seats (provided by driver) for all the invited members + leader
    - Assign driver with `user_id`
    - Mark trip as public
    - Create polygon routes if not created.
    - All of the grouped-passengers will be added to reserved list with a unique `group_id`
    - Seats available will be reduced accordingly.
|-- After all grouped-passengers accept driver offers. Following should happen.
    - Payment will occurred.

URIs:
- [1] passenger/make/offer
- [2] driver/make/offer
- [3] driver/request/search
