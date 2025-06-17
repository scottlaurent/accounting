<?php

declare(strict_types=1);

namespace Scottlaurent\Accounting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class JournalTransaction extends Model
{
    protected string $table = 'accounting_journal_transactions';
    
    public bool $incrementing = false;
    
    protected array $guarded = ['id'];
    
    protected array $casts = [
        'post_date' => 'datetime',
        'tags' => 'array',
        'debit' => 'int',
        'credit' => 'int',
        'ref_class_id' => 'int',
    ];
    
    protected $fillable = [
        'journal_id',
        'debit',
        'credit',
        'currency',
        'memo',
        'post_date',
        'tags',
        'ref_class',
        'ref_class_id',
        'transaction_group',
    ];
    
    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function (self $transaction): void {
            $transaction->id = Uuid::uuid4()->toString();
        });
        
        static::deleted(function (self $transaction): void {
            $transaction->journal?->resetCurrentBalances();
        });
    }
    
    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }
    
    public function referencesObject(Model $object): self
    {
        $this->update([
            'ref_class' => $object::class,
            'ref_class_id' => $object->id,
        ]);
        
        return $this;
    }
    
    public function getReferencedObject(): ?Model
    {
        if (! $this->ref_class) {
            return null;
        }
        
        $class = new $this->ref_class;
        return $class->find($this->ref_class_id);
    }
    
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }
}
