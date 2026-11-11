<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    public function created(Model $model)
    {
        activity()
            ->performedOn($model)
            ->log('Membuat data baru pada ' . class_basename($model));
    }

    public function updated(Model $model)
    {
        activity()
            ->performedOn($model)
            ->log('Memperbarui data ' . class_basename($model));
    }

    public function deleted(Model $model)
    {
        activity()
            ->performedOn($model)
            ->log('Menghapus data ' . class_basename($model));
    }
}