<?php

namespace Modules\User\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request)
    {
        return [
            $this->merge(
                Arr::only(parent::toArray($request), [
                    'id',
                    'name',
                    'email',
                ])
            ),
            'status' => $this->status,
            'role' => $this->getRole($this->role_id),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user_details' => $this->user_details,
            'user_image' => $this->user_details?->changed_user_image,
            'attachments' => new UserAttachmentResource($this->whenLoaded('user_attachment')),
        ];
    }

    public function getRole($role_id)
    {
        return match ($role_id) {
            User::ADMIN => 'ADMIN',
            User::DEPARTMENT_MANAGER => 'DEPARTMENT_MANAGER',
            User::EMPLOYEE => 'EMPLOYEE',
            default => 'Role Not Found'
        };
    }
}
