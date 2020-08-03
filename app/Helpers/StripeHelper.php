<?php

namespace App\Helpers;

use \Stripe\Account as StripeAccount;
use \Stripe\Charge as StripeCharge;
use \Stripe\Payout as StripePayout;
use \Stripe\Refund as StripeRefund;
use \Stripe\Transfer as StripeTransfer;

class StripeHelper
{
    public static function init()
    {
        \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));

        return new static;
    }

    public static function convertToCents($amount)
    {
        return round($amount * 100);
    }

    public static function createDriverAccount(array $payload, \App\Models\User $user)
    {
        self::init();

        $birthDate = \Carbon\Carbon::createFromFormat('m/d/Y H:i:s', $user->getMeta('birth_date') . ' 00:00:00');

        $city  = $user->getCityTitleAttribute();//usercity;//->name;
        $state = $user->getStateTitleAttribute();

        $state = constants('states.' . $state, $state);


        $account = StripeAccount::create([
            'type'             => 'custom',
            'country'          => 'US',
            'email'            => $user->email,
            'external_account' => [
                'object'              => 'bank_account',
                'country'             => 'US',
                'currency'            => 'USD',
                'account_holder_name' => $payload['account_title'],
                'account_holder_type' => 'individual',
                'routing_number'      => $payload['routing_number'],
                'account_number'      => $payload['account_number'],
            ],
            'payout_schedule'  => [
                'delay_days'    => 2,
                'interval'      => 'weekly',
                'weekly_anchor' => 'monday',
            ],
            'legal_entity'     => [
                'address'    => [
                    'city'        => $city,
                    'country'     => 'US',
                    'line1'       => $payload['address'],
                    'postal_code' => $payload['postal_code'],
                    'state'       => $state,
                ],
                'dob'        => [
                    'day'   => $birthDate->format('d'),
                    'month' => $birthDate->format('m'),
                    'year'  => $birthDate->format('Y'),
                ],
                'first_name' => $user->first_name,
                'last_name'  => $user->last_name,
                // 'personal_id_number' => $payload['personal_id_number'],
                'ssn_last_4' => $payload['ssn_last_4'],
                'type'       => 'individual',
            ],
            'tos_acceptance'   => [
                'date'       => \Carbon\Carbon::now()->getTimestamp(),
                'ip'         => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);

        return $account;
    }

    public static function createCustomer($customer)
    {
        self::init();

        try {

            $stripeCustomer = StripeCustomer::create($customer);

            info('Stripe Customer: ' . print_r($stripeCustomer, true));

            return $stripeCustomer;

        } catch (\Exception $e) {
            info('createCustomer: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            return false;
        }
    }

    public static function createCustomerFromCard($card_data, $customer_id = false)
    {
        self::init();

        try {
            $cc_info = array(
                'email'  => $card_data['email'],
                'source' => array(
                    'object'    => 'card',
                    'number'    => $card_data['card_number'],
                    'exp_month' => $card_data['card_expiry_month'],
                    'exp_year'  => $card_data['card_expiry_year'],
                    'cvc'       => $card_data['card_cvv'],
                    'currency'  => Setting::option('currency_code', 'GBP'),
                ),
            );

            if (!$customer_id) {
                $stripeCustomer = StripeCustomer::create($cc_info);
            } else {
                $stripeCustomer = StripeCustomer::update($customer_id, $cc_info);
            }

            info('Stripe Customer: ' . print_r($stripeCustomer, true));

            return $stripeCustomer;

        } catch (\Exception $e) {
            info('SaveCreditCard: ' . $e->getMessage() . ' File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            return false;
        }
    }

    public static function createCustomerFromToken($user, $stripe_token, $customer_id = false)
    {
        self::init();

        try {
            $info = [
                'email'  => $user['email'],
                'source' => $stripe_token,
            ];

            if (!$customer_id) {
                $stripeCustomer = \Stripe\Customer::create($info);
            } else {
                $stripeCustomer = \Stripe\Customer::update($customer_id, $info);
            }

            info('Stripe Customer: ' . print_r((array) $stripeCustomer, true));

            return $stripeCustomer;

        } catch (\Exception $e) {
            info('StripeCustomer: ' . $e->getMessage());
            return false;
        }
    }

    public static function payViaStripe($stripe_id, $amount, $transferGroup)
    {
        self::init();

        $amount = self::convertToCents($amount);

        try {

            $charge = StripeCharge::create([
                'amount'         => $amount,
                'currency'       => 'USD',
                'customer'       => $stripe_id,
                'transfer_group' => 'TRIP' . $transferGroup,
            ]);

            info($charge);

        } catch (\Exception $e) {
            info('Stripe Payment: ' . print_r($e->getMessage(), true));

            return false;
        }

        return $charge;
    }

    public static function transferAmount($account_id, $amount, $transferGroup)
    {
        self::init();

        $amount = self::convertToCents($amount);

        try {

            $transfer = StripeTransfer::create([
                'amount'      => $amount,
                'currency'    => 'USD',
                'destination' => $account_id,
            ] + (!intval($transferGroup) ? [] : ['transfer_group' => 'TRIP' . $transferGroup]));

            info(print_r($transfer, true));

        } catch (\Exception $e) {
            info('Stripe Transfer: ' . print_r($e->getMessage(), true));

            return false;
        }

        return $transfer;
    }

    public static function requestPayout($account_id, $amount, $payoutType = 'standard')
    {
        self::init();

        $amount     = self::convertToCents($amount);
        $payoutType = 'standard' === strtolower($payoutType) ? 'standard' : 'instant';

        try {

            $payout = StripePayout::create([
                'amount'   => $amount,
                'currency' => 'USD',
                'method'   => $payoutType,
            ], [
                'stripe_account' => $account_id,
            ]);

            info(print_r($payout, true));

        } catch (\Exception $e) {
            info('Stripe Payouts: ' . print_r($e->getMessage(), true));

            return false;
        }

        return $payout;
    }

    public static function refund($chargeId, $amountToRefund = null)
    {
        self::init();

        try {
            $refundPayload = [
                'charge' => $chargeId,
                'reason' => 'requested_by_customer',
            ];

            if (null !== $amountToRefund) {
                $refundPayload['amount'] = self::convertToCents($amountToRefund);
            }

            $refund = StripeRefund::create($refundPayload);

            info($refund);

            if (strtolower($refund['status']) !== 'succeeded') {
                return false;
            }

        } catch (\Exception $e) {
            info('Stripe Refund: ' . print_r($e->getMessage(), true));

            // If already refunded then try to get refund_id from existing data.
            if (ends_with($e->getMessage(), 'has already been refunded.')) {
                $refund = StripeRefund::all([
                    'charge' => $chargeId,
                    'limit'  => 1,
                ]);
                $refund = data_get($refund, 'data.0', []);
            } else {
                return false;
            }
        }

        return $refund;
    }

}
