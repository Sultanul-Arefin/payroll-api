<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;

class UserAttachment extends Model
{
    use HasFactory;

    public const HEADING_TYPE = ['contract' => 1, 'official' => 2, 'others' => 3];
    public const CONTRACT_ITEM_TYPE = ['contract_letter' => 1, 'national_id_card' => 2, 'cv' => 3, 'change_contract_letter' => 4];
    public const OFFICIAL_ITEM_TYPE = ['passport_file' => 1, 'visa' => 2, 'work_permit' => 3, 'immigration_application' => 4];
    public const OTHERS_ITEM_TYPE = ['other_docs_1' => 1, 'other_docs_2' => 2, 'other_docs_3' => 3, 'other_docs_4' => 4, 'other_docs_5' => 5];

    protected $fillable = ['user_id', 'file_name', 'heading_type', 'item_type', 'company_id'];

    protected static function newFactory()
    {
        return \Modules\User\Database\factories\UserAttachmentFactory::new();
    }

    protected function fileName() : Attribute
    {
        return Attribute::make(
            get: fn($value) => ($value ? env('APP_URL') . '/storage/' .$value : null)
        );
    }
}
