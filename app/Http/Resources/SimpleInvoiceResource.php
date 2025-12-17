<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SimpleInvoiceResource extends JsonResource
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
            'dps_id' => $this->dps_id,
            'access_key' => $this->access_key,
            'dps_number' => $this->dps_number,
            'dps_series' => $this->dps_series,
            'status' => $this->status,
            'status_message' => $this->status_message,
        ];
    }
}
