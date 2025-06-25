<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'price' => $this->price,
            'yearly_discount' => $this->yearly_discount,
            'role' => $this->role,
            'recommended' => $this->recommended,
            'created_at' => $this->created_at,
            'updated_at' => $this->modified_at,
            'user_count' => $this->userSubscriptions->count(),
        ];
    }
}
