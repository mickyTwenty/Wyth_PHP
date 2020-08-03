<?php

namespace App\Models;

use App\Classes\FirebaseHandler;
use App\Classes\PushNotification;
use App\Events\NewNotificationAdded;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_type',
        'notification',
        'notification_type',
        'notification_data',
        'payload',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'notification_data' => 'array',
        'payload' => 'array',
        'is_read' => 'boolean',
    ];

    /**
     * Identify the user_type for notifications
     *
     * @var string
     */
    private $userType;

    /**
     * Notification channel
     * Allows: push, email, sms
     *
     * @var array
     */
    private $notifications = [];

    /**
     * @var User
     */
    private $owner;

    public function createNotification(array $data)
    {
        return $this->setNotificationModelData($data);
    }

    /**
     * Make this notification non-actionable by default.
     *
     * @return App\Models\Notification
     */
    public function notActionable()
    {
        $payload = $this->getNotificationModelData();

        $payload['notification_data']['actionable'] = false;

        return $this->setNotificationModelData($payload);
    }

    public function customPayload(array $payload)
    {
        $previousPayload = $this->getNotificationModelData();

        // Adjust payload data so that notification data can be saved appropriately
        $payload['data_title']        = $previousPayload['notification'];
        $payload['data_message']      = $previousPayload['notification_data']['message'];
        $payload['data_click_action'] = $payload['click_action'];

        unset($payload['click_action']);

        $payload = [
            'payload' => [
                'notification' => [
                    'title'        => $payload['data_title'],
                    'message'      => $payload['data_message'],
                    'click_action' => $payload['data_click_action'],
                ],
                'data' => $payload
            ]
        ];

        $payload = $previousPayload + $payload;

        return $this->setNotificationModelData($payload);
    }

    /**
     * Do all the processing for notification
     *
     * @return App\Models\Notification
     */
    public function build()
    {
        if ( is_null($this->getUserType()) ) {
            throw new Exception('Please define user_type first before proceeding.');
        }

        $attributes = $this->getNotificationModelData() + [
            'user_type' => $this->getUserType(),
        ];

        $notification = $this->getOwner()->notifications()->create( $attributes );

        event(new NewNotificationAdded($notification, $this->getOwner()));

        $this->throwNotifications($notification);

        return $notification;
    }

    public function throwNotifications(self $notification)
    {
        if ( in_array('push', $this->getNotifications()) ) {
            $payload             = collect($notification->payload['data']);
            $notificationPayload = collect($notification->payload['notification']);

            // Chnaged to proxy push, it seems to be more feasilble way to handle push and badges.
            // NOTE: Badges will be handled by firebase itself, no need define here.
            FirebaseHandler::update('/notifications/'.$notification->user_id, [
                'data' => $payload->toArray(),
                'notification' => $notificationPayload->toArray() + [
                    'sound' => 'default',
                ],
            ]);

            /*PushNotification::sendToUserConditionally($notification->user_id, [
                'content' => [
                    'title'        => $notification->notification,
                    'message'      => $data->get('message', $notification->notification),
                    'click_action' => $action,
                    'sound'        => 'default',
                    'badge'        => $this->getOwner()->getMetaDefault('unread_notifications', 0),
                ],
                'data' => $payload->toArray(),
            ]);*/
        }
    }

    public function disableThrowing()
    {
        return $this->throwNotificationsVia([]);
    }

    public function setUserType($value)
    {
        $this->userType = $value;

        return $this;
    }

    private function getUserType()
    {
        return $this->userType;
    }

    public function throwNotificationsVia($value)
    {
        if ( !is_array($value) ) {
            $value = [$value];
        }

        $this->notifications = $value;

        return $this;
    }

    private function getNotifications()
    {
        return $this->notifications;
    }

    public function setNotificationModelData(array $data)
    {
        $this->notificationModelData = $data;

        return $this;
    }

    public function getNotificationModelData()
    {
        return $this->notificationModelData;
    }

    public function setOwner(User $user, $userType)
    {
        $this->owner = $user;

        return $this->setUserType($userType);
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
