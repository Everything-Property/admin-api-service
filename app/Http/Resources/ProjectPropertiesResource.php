<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectPropertiesResource extends JsonResource
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
            'project_id' => $this->project_id,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'plot_size' => $this->plot_size,
            'amenities' => $this->amenities,
            'description' => $this->description,
            'image' => $this->image,
            'house_type' => $this->house_type,
            'totalUnitAvailable' => $this->totalUnitAvailable,
            'price' => $this->price,
            'total_unit_available' => $this->total_unit_available,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
