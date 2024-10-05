<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMediaResource extends JsonResource
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
            'original_file_name' => $this->original_file_name,
            'file_name' => $this->file_name,
            'fingerprint' => $this->fingerprint,
            'token' => $this->token,
            'primary_image' => $this->primary_image,
            'rotate_degree' => $this->rotate_degree,
            'created_at' => $this->created_at,
            'updated_at' => $this->modified_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
