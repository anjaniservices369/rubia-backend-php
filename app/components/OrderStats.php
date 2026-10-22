<?php

namespace app\components;

use Yii;
use yii\helpers\Html;
use yii\base\Component;
use app\modules\admin\models\Orders;


class OrderStats extends Component
{

    public function getStatusButtons($model)
    {

        switch ($model->status) {

            case Orders::STATUS_ACCEPTED:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_PACKING . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Order Packing
             </button>&nbsp;
             <button type="button" class="btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
             </button>';
                break;

            case Orders::STATUS_PACKING:

                return  '
                <button type="button" class="btn btn-success float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_DELIVERY_BOY_PICKED . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Delivery Boy Picked
                </button><button type="button" class="btn btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order';
                break;

            case Orders::STATUS_DELIVERY_BOY_PICKED:

                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_ONTHE_WAY . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> On The Way
                </button>&nbsp;
                <button type="button" class="btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
                </button>';
                break;

            case Orders::STATUS_DELIVERY_BOY_PICKED:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_ONTHE_WAY . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Order On the Way
                </button>&nbsp;
                <button type="button" class="btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
                </button>';
                break;

            case Orders::STATUS_ONTHE_WAY:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_REACHED . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Reached Delivery Location
                </button> &nbsp;
                <button type="button" class="btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
                </button>';
                break;
            case Orders::STATUS_REACHED:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_DELIVERED . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Delivered
                </button>&nbsp;
                <button type="button" class="btn btn-danger float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
                </button>';

                break;
            case Orders::STATUS_CANCELLED_BY_OWNER:
                return '<button type="button" class="btn btn-danger float-right" disabled "><i class="far fa-check-square"></i> Order Cancelled By Restaurant Owner
                </button>';

                break;
            case Orders::STATUS_CANCELLED_BY_USER:
                return '<button type="button" class="btn btn-danger float-right" disabled "><i class="far fa-check-square"></i> Order Cancelled By User
                </button>';

                break;
            case Orders::STATUS_CANCELLED_BY_ADMIN:
                return '<button type="button" class="btn btn-danger float-right" disabled "><i class="far fa-check-square"></i> Order Cancelled By Admin
                </button>';

                break;
            case Orders::STATUS_DELIVERED:
                return '<button type="button" class="btn btn-success float-right" disabled "><i class="far fa-check-square"></i> Order Delivered
                </button>';

                break;

            case Orders::STATUS_CANCELLED_BY_DELIVERY_BOY:
                return '<button type="button" class="btn btn-danger float-right" disabled "><i class="far fa-check-square"></i> Order Cancelled By Delivery Boy
                </button>';

                break;
            default:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_ACCEPTED . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Accept Order
            </button>
            <button type="button" class="btn btn-danger float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_OWNER . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Reject Order
            </button>
            ';
        }
    }

    public function getTakeawayStatusButtons($model)
    {

        switch ($model->status) {

            case Orders::STATUS_ACCEPTED:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_COOKING . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Order Cooking
             </button>&nbsp;
             <button type="button" class="btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order
             </button>';
                break;

            case Orders::STATUS_COOKING:

                return  '
                <button type="button" class="btn btn-success float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_DELIVERED . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Order Delivered
                </button><button type="button" class="btn btn btn-danger float-right"  id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_ADMIN . '" data-id="' . $model->id . '"><i class="fas fa-window-close    "></i> Cancel Order';
                break;


            case Orders::STATUS_DELIVERED:
                return '<button type="button" class="btn btn-success float-right" disabled "><i class="far fa-check-square"></i> Order Delivered
                </button>';

                break;


            default:
                return '<button type="button" class="btn btn-success float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_ACCEPTED . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Accept Order
            </button>
            <button type="button" class="btn btn-danger float-right" id="order-status_' . $model->id . '" value="' . Orders::STATUS_CANCELLED_BY_OWNER . '" data-id="' . $model->id . '"><i class="far fa-check-square"></i> Reject Order
            </button>
            ';
        }
    }
}
