<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\Translation;

class PageSection extends Model
{
    protected $fillable = [
        'page_id',
        'type',
        'title',
        'subtitle',
        'content',
        'button_text',
        'button_link',
        'image',
        'order',
        'is_active'
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'content' => 'array',
        'button_text' => 'array',
        'is_active' => 'boolean'
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function images()
    {
        return $this->hasMany(PageSectionImage::class, 'section_id')
            ->orderBy('order');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS (IMPORTANTE)
    |--------------------------------------------------------------------------
    */

    public function getTitle()
    {
        return Translation::text($this->title);
    }

    public function getSubtitle()
    {
        return Translation::text($this->subtitle);
    }

    public function getContent()
    {
        return Translation::text($this->content);
    }

    public function getContentParagraphs()
    {
        if (!$this->getContent()) {
            return [];
        }

        return array_filter(
            array_map('trim', preg_split("/\r?\n/", $this->getContent()))
        );
    }

    public function getButtonText()
    {
        return Translation::text($this->button_text);
    }
}
