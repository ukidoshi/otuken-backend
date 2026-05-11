<?php

namespace App\Models;

use App\Enums\NewsStatus;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class News extends Model implements HasMedia
{
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use Sluggable;
    use SoftDeletes;

    public array $translatable = [
        'title',
        'excerpt',
        'content_blocks',
        'seo_title',
        'seo_description',
    ];

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content_blocks',
        'status',
        'publish_at',
        'unpublish_at',
        'locale',
        'seo_title',
        'seo_description',
        'seo_image_alt',
        'canonical',
        'author_id',
        'published_by_id',
        'approved_at',
        'is_actuality_highlight',
    ];

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'unpublish_at' => 'datetime',
            'approved_at' => 'datetime',
            'status' => NewsStatus::class,
            'is_actuality_highlight' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (News $news): void {
            if (! $news->is_actuality_highlight) {
                return;
            }

            static::query()
                ->where('is_actuality_highlight', true)
                ->when($news->exists, fn (Builder $query): Builder => $query->whereKeyNot($news->getKey()))
                ->update(['is_actuality_highlight' => false]);
        });
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', NewsStatus::Published->value)
            ->where(function (Builder $builder): void {
                $builder->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('unpublish_at')
                    ->orWhere('unpublish_at', '>', now());
            });
    }

    /**
     * Переводит в архив записи со статусом «Опубликовано», у которых наступила дата снятия с публикации.
     */
    public static function archiveExpiredByUnpublishDate(): int
    {
        return static::query()
            ->where('status', NewsStatus::Published->value)
            ->whereNotNull('unpublish_at')
            ->where('unpublish_at', '<=', now())
            ->update(['status' => NewsStatus::Archived->value]);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_id');
    }

    public function previewTokens(): HasMany
    {
        return $this->hasMany(NewsPreviewToken::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover_image')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('seo_image')
            ->singleFile()
            ->useDisk('public');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        foreach ([400, 800, 1400] as $width) {
            $this->addMediaConversion("webp-{$width}")
                ->width($width)
                ->nonOptimized()
                ->nonQueued();
        }
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'onUpdate' => true,
                'method' => static function (string $string): string {
                    return str($string)->slug()->toString();
                },
            ],
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'title',
                'slug',
                'status',
                'publish_at',
                'unpublish_at',
                'locale',
                'seo_title',
                'seo_description',
                'seo_image_alt',
                'canonical',
                'is_actuality_highlight',
            ])
            ->logOnlyDirty();
    }
}
