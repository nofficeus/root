<?php

namespace App\Http\Resources;

use App\Models\PaymentAccount;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PaymentAccount
 */
class PaymentAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        if (is_null($this->resource)) {
            return [];
        }

        return [
            'id'                              => $this->id,
            'reference'                       => $this->reference,
            'currency'                        => $this->currency,
            'min_transferable'                => $this->min_transferable->getValue(),
            'max_transferable'                => $this->max_transferable->getValue(),
            'balance_on_trade'                => $this->balance_on_trade->getValue(),
            'formatted_balance_on_trade'      => $this->formatted_balance_on_trade,
            'balance'                         => $this->balance->getValue(),
            'formatted_balance'               => $this->formatted_balance,
            'available'                       => $this->available->getValue(),
            'formatted_available'             => $this->formatted_available,
            'total_received'                  => $this->total_received->getValue(),
            'formatted_total_received'        => $this->formatted_total_received,
            'total_pending_receive'           => $this->total_pending_receive->getValue(),
            'formatted_total_pending_receive' => $this->formatted_total_pending_receive,
            'total_sent'                      => $this->total_sent->getValue(),
            'formatted_total_sent'            => $this->formatted_total_sent,
            'symbol'                          => $this->symbol,
            'created_at'                      => $this->created_at,
            'updated_at'                      => $this->updated_at,
            'user'                            => UserResource::make($this->whenLoaded('user')),
        ];
    }
}
