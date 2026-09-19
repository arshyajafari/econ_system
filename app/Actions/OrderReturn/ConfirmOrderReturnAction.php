<?php

namespace App\Actions\OrderReturn;

use App\Enums\OrderReturnStatus;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

class ConfirmOrderReturnAction {
    public function execute(OrderReturn $orderReturn): OrderReturn {
        return DB::transaction(function () use ($orderReturn) {
            $orderReturn=OrderReturn::query()->lockForUpdate()->with(['items.orderItem','customer','employee'])->findOrFail($orderReturn->id);
            if($orderReturn->status!==OrderReturnStatus::PENDING) throw new BusinessRuleException('فقط برگشت در وضعیت pending قابل تأیید است.');
            if($orderReturn->items->isEmpty()) throw new BusinessRuleException('برگشت سفارش حداقل باید یک آیتم داشته باشد.');

            $order=Order::query()->lockForUpdate()->with(['items','returns.items'])->findOrFail($orderReturn->order_id);
            if($order->status!==OrderStatus::COMPLETED) throw new BusinessRuleException('فقط سفارش تکمیل‌شده قابل تأیید مرجوعی است.');

            $requested=$orderReturn->items->groupBy('order_item_id')->map(fn($items)=>[
                'total'=>$items->sum('quantity'),
                'free'=>$items->sum('free_quantity'),
            ]);

            foreach($requested as $orderItemId=>$req){
                $orderItem=$order->items->firstWhere('id',$orderItemId);
                if(!$orderItem) throw new BusinessRuleException('آیتم سفارش مربوط به برگشت پیدا نشد.');
                $requestedFree=(int)$req['free']; $requestedPaid=(int)$req['total']-$requestedFree;
                $previous=$order->returns->where('id','!=',$orderReturn->id)->reject(fn($return)=>in_array($return->status,[OrderReturnStatus::DRAFT,OrderReturnStatus::CANCELLED],true))->flatMap(fn($return)=>$return->items)->where('order_item_id',$orderItemId);
                $returnedFree=$previous->sum('free_quantity'); $returnedPaid=$previous->sum('quantity')-$returnedFree;
                if($requestedPaid<0) throw new BusinessRuleException('تعداد رایگان مرجوعی نامعتبر است.');
                if($requestedPaid>((int)$orderItem->quantity-$returnedPaid)) throw new BusinessRuleException('مقدار پولی قابل برگشت برای این آیتم کافی نیست.');
                if($requestedFree>((int)$orderItem->offer_free_quantity-$returnedFree)) throw new BusinessRuleException('مقدار رایگان قابل برگشت برای این آیتم کافی نیست.');
            }

            foreach($orderReturn->items as $item){
                if(!$item->orderItem) throw new BusinessRuleException('آیتم سفارش مربوط به برگشت پیدا نشد.');
                if((int)$item->product_id!==(int)$item->orderItem->product_id) throw new BusinessRuleException('محصول آیتم مرجوعی با محصول آیتم سفارش مطابقت ندارد.');
            }

            $orderReturn->status=OrderReturnStatus::CONFIRMED; $orderReturn->save();
            return $orderReturn->fresh(['order','customer','employee','items.product','items.orderItem']);
        });
    }
}
