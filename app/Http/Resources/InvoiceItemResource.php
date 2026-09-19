<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'=>$this->public_id,
            'order_item_id'=>$this->whenLoaded('orderItem',fn()=> $this->orderItem?->public_id),
            'product'=>$this->whenLoaded('product',fn()=>['id'=>$this->product->public_id,'code'=>$this->product->code,'title'=>$this->product->title]),
            'quantity'=>$this->quantity,
            'free_quantity'=>$this->free_quantity,
            'total_quantity'=>(int)$this->quantity+(int)$this->free_quantity,
            'unit_price'=>$this->unit_price,
            'total_price'=>$this->total_price,
            'offer_type'=>$this->whenLoaded('orderItem',fn()=> $this->orderItem?->offer_type),
            'offer_buy_quantity'=>$this->whenLoaded('orderItem',fn()=> $this->orderItem?->offer_buy_quantity),
            'offer_free_quantity_per_cycle'=>$this->whenLoaded('orderItem',fn()=> $this->orderItem?->offer_free_quantity),
            'offer_title'=>$this->whenLoaded('orderItem',fn()=> $this->orderItem?->offer_title),
            'description'=>$this->description,
            'created_at'=>$this->created_at?->toISOString(),
            'updated_at'=>$this->updated_at?->toISOString(),
        ];
    }
}
