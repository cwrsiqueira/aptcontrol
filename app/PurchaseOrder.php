<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_AWAITING_QUOTES = 'awaiting_quotes';
    public const STATUS_AWAITING_APPROVAL = 'awaiting_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FINISHED = 'finished';

    protected $fillable = [
        'requester_id',
        'created_by',
        'request_date',
        'description',
        'notes',
        'status',
        'decided_by',
        'decided_at',
        'decision_note',
        'finalized_by',
        'finalized_at',
    ];

    protected $casts = [
        'request_date' => 'date',
        'decided_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decisionUser()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function finalizedUser()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('id');
    }

    public function quotes()
    {
        return $this->hasMany(PurchaseQuote::class)->orderBy('amount')->orderBy('id');
    }

    public function getNumberAttribute(): string
    {
        return 'PC-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? 'Status desconhecido';
    }

    public function getStatusBadgeAttribute(): string
    {
        return [
            self::STATUS_DRAFT => 'secondary',
            self::STATUS_AWAITING_QUOTES => 'info',
            self::STATUS_AWAITING_APPROVAL => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_FINISHED => 'primary',
        ][$this->status] ?? 'secondary';
    }

    public function canEdit(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Rascunho',
            self::STATUS_AWAITING_QUOTES => 'Aguardando orçamentos',
            self::STATUS_AWAITING_APPROVAL => 'Aguardando aprovação',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_REJECTED => 'Reprovado',
            self::STATUS_FINISHED => 'Finalizado',
        ];
    }
}
