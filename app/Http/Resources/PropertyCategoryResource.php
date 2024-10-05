<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyCategoryResource extends JsonResource
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
            'active' => $this->active,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'sold_by_area' => $this->sold_by_area,
            'created_at' => $this->created_at,
            'updated_at' => $this->modified_at,
        ];
    }
}
