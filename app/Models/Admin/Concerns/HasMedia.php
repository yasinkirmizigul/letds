<?php
namespace App\Models\Admin\Concerns;

use App\Models\Admin\Media\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMedia
{
    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'order'])
            ->withTimestamps()
            ->orderBy('mediables.order');
    }

    public function mediaIn(string $collection = 'default'): MorphToMany
    {
        return $this->media()->wherePivot('collection', $collection);
    }
}
