<?php

namespace Modules\User\Http\Resources;

use App\Models\User;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use JsonSerializable;
use Modules\EmployeeSalaryItems\Entities\EmployeeSalaryItem;

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
            'role_id' => $this->role_id,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'user_details' => $this->user_details,
            'user_image' => $this->user_details?->changed_user_image,
            'attachments' => new UserAttachmentResource($this->whenLoaded('user_attachment')),
            'is_editable' => auth()->user()->role_id === User::ADMIN ? 1 : 0,
            'pay_frequency' => $this->getPaymentType()
        ];
    }

    public function getPaymentType()
    {
        $amount = EmployeeSalaryItem::query()
            ->whereHas(
                'salaryItemsName', function (Builder $builder) {
                    $builder->where('name', 'Wages')
                        ->whereHas(
                            'salaryItemsCategory', function (Builder $builder) {
                                $builder->where('id', 1);
                            }
                        );
                }
            )
            ->where('company_id', auth()->user()->company_id)
            ->where('employee_id', $this->id)
            ->get()->sum('amount');
        if($amount > 0){
            return "Monthly";
        }
        return "Hourly";
    }

    public function getRole($role_id)
    {
        return match ($role_id) {
            User::ADMIN => 'ADMIN',
            User::MANAGER => 'MANAGER',
            User::DEPARTMENT_MANAGER => 'DEPARTMENT_MANAGER',
            User::EMPLOYEE => 'EMPLOYEE',
            default => 'Role Not Found'
        };
    }
}
