<?php

namespace App\Actions\OrderReturn;

use App\Enums\OrderReturnStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\OrderReturn;
use Illuminate\Support\Facades\DB;

class UpdateOrderReturnAction {
    public function execute(OrderReturn $orderReturn, array $data): OrderReturn {
        return DB::transaction(function () use ($orderReturn,$data) {
            $orderReturn=OrderReturn::query()->lockForUpdate()->with(['items','order.items','order.returns.items'])->findOrFail($orderReturn->id);
            if($orderReturn->status!==OrderReturnStatus::DRAFT) throw new BusinessRuleException('فقط برگشت در وضعیت draft قابل ویرایش است.');

            $order=$orderReturn->order;
            $orderReturn->update(['description'=>$data['description']??null]);
            $orderReturn->items()->delete();

            foreach($data['items'] as $itemData){
                $orderItem=$order->items->firstWhere('public_id',$itemData['order_item_id']);
                if(!$orderItem) throw new BusinessRuleException('آیتم انتخاب‌شده متعلق به این سفارش نیست.');
                $quantity=(int)$itemData['quantity'];
                $freeQuantity=min($quantity,(int)($itemData['free_quantity']??0));
                $requestedPaid=$quantity-$freeQuantity;
                $previous=$order->returns->where('id','!=',$orderReturn->id)->reject(fn($return)=>in_array($return->status,[OrderReturnStatus::DRAFT,OrderReturnStatus::CANCELLED],true))->flatMap(fn($return)=>$return->items)->where('order_item_id',$orderItem->id);
                $returnedFree=$previous->sum('free_quantity');
                $returnedPaid=$previous->sum('quantity')-$returnedFree;
                if($quantity<=0) throw new BusinessRuleException('مقدار برگشتی باید بیشتر از صفر باشد.');
                if($requestedPaid>((int)$orderItem->quantity-$returnedPaid)) throw new BusinessRuleException('مقدار پولی قابل برگشت برای این آیتم کافی نیست.');
                if($freeQuantity>((int)$orderItem->offer_free_quantity-$returnedFree)) throw new BusinessRuleException('مقدار رایگان قابل برگشت برای این آیتم کافی نیست.');

                $orderReturn->items()->create([
                    'order_item_id'=>$orderItem->id,'product_id'=>$orderItem->product_id,'quantity'=>$quantity,'free_quantity'=>$freeQuantity,
                    'unit_price'=>$orderItem->unit_price,'total_price'=>round($requestedPaid*(float)$orderItem->unit_price,2),'description'=>$itemData['description']??null,
                ]);
            }
            return $orderReturn->fresh(['order','customer','employee','items.product']);
        });
    }
}
