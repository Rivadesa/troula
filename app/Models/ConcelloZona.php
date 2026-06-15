<?php

namespace App\Models;

use Database\Factories\ConcelloZonaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConcelloZona extends Model
{
    /** @use HasFactory<ConcelloZonaFactory> */
    use HasFactory;

    protected $table = 'concello_zona';

    protected $fillable = [
        'concello',
        'zona_id',
    ];

    /**
     * @return BelongsTo<ZonaPorte, $this>
     */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaPorte::class, 'zona_id');
    }
}
