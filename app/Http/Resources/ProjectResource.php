<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'name' => $this->name,
            'project_type' => $this->project_type,
            'keyword' => $this->keyword,
            'plot_size' => $this->plot_size,
            'description' => $this->description,
            'amenities' => $this->amenities,
            'state' => $this->state,
            'locality' => $this->locality,
            'address' => $this->address,
            'longitude' => $this->longitude,
            'latitude' => $this->latitude,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'location' => $this->location,
            'media' => ProjectMediaResource::collection($this->media),
            'properties' => ProjectPropertiesResource::collection($this->properties),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
