<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'kicker' => $this->kicker,
            'text' => $this->text,
            'image' => $this->image_url,
            'link_label' => $this->link_label,
            'link_url' => $this->link_url,
            'position' => $this->position,
            'sort_order' => $this->sort_order,
            'active' => $this->active,
            'created_at' => $this->created_at,
        ];
    }
}