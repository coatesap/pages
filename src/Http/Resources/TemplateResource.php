<?php

namespace Optimus\Pages\Http\Resources;

use Illuminate\Http\Resources\Json\Resource;

class TemplateResource extends Resource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'component_name' => $this->component_name,
            'is_selectable' => $this->is_selectable
        ];
    }
}