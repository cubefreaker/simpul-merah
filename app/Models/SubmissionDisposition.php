<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmissionDisposition extends Model
{
    use HasFactory;

    protected $fillable = [
        'form_submission_id',
        'from_user_id',
        'to_user_id',
        'action',
        'from_stage',
        'to_stage',
        'catatan',
        'instruksi',
    ];

    protected $casts = [
        'instruksi' => 'array',
    ];

    /**
     * Valid action values
     */
    const ACTION_DISPOSISI     = 'disposisi';
    const ACTION_VERIFIKASI    = 'verifikasi';
    const ACTION_APPROVE       = 'approve';
    const ACTION_REJECT        = 'reject';
    const ACTION_UPLOAD_DRAFT  = 'upload_draft';
    const ACTION_UPDATE_STATUS = 'update_status';

    /**
     * Human-readable action labels
     */
    public static function getActionLabel(string $action): string
    {
        return match($action) {
            self::ACTION_DISPOSISI     => 'Disposisi',
            self::ACTION_VERIFIKASI    => 'Verifikasi',
            self::ACTION_APPROVE       => 'Disetujui',
            self::ACTION_REJECT        => 'Ditolak',
            self::ACTION_UPLOAD_DRAFT  => 'Upload Draft',
            self::ACTION_UPDATE_STATUS => 'Update Status',
            default                    => ucfirst($action),
        };
    }

    /**
     * Icon/color per action untuk UI timeline
     */
    public static function getActionColor(string $action): string
    {
        return match($action) {
            self::ACTION_DISPOSISI     => 'text-blue-600 bg-blue-50',
            self::ACTION_VERIFIKASI    => 'text-indigo-600 bg-indigo-50',
            self::ACTION_APPROVE       => 'text-emerald-600 bg-emerald-50',
            self::ACTION_REJECT        => 'text-red-600 bg-red-50',
            self::ACTION_UPLOAD_DRAFT  => 'text-orange-600 bg-orange-50',
            self::ACTION_UPDATE_STATUS => 'text-gray-600 bg-gray-50',
            default                    => 'text-gray-600 bg-gray-50',
        };
    }

    /**
     * Get the form submission this disposition belongs to
     */
    public function formSubmission()
    {
        return $this->belongsTo(FormSubmission::class);
    }

    /**
     * Get the user who sent this disposition
     */
    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    /**
     * Get the user who received this disposition
     */
    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }
}
