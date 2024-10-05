<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyRequestResource extends JsonResource
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
            'property_type_id' => $this->property_type_id,
            'property_category_id' => $this->property_category_id,
            'state_id' => $this->state_id,
            'state_locality_id' => $this->state_locality_id,
            'full_name' => $this->full_name,
            'user_type' => $this->user_type,
            'email' => $this->email,
            'phone' => $this->phone,
            'additional_information' => $this->additional_information,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'last_notified_at' => $this->last_notified_at,
            'bedroom' => $this->bedroom,
            'bathroom' => $this->bathroom,
            'user_id' => $this->user_id,
        ];
    }
}
