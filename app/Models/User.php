<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'referral_code',
        'username',
        'image',
        'is_profile_complete',
        'otp',
        'is_verified',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_profile_complete' => 'boolean',
        ];
    }

    /**
     * Get the URL for the user's profile image.
     *
     * @return string|null
     */
    // public function getImageAttribute(): ?string
    // {
    //     return $this->image ? Storage::url($this->image) : null;
    // }

    public function sports()
    {
        return $this->belongsToMany(Sport::class, 'sport_user')->withTimestamps();
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class, 'team_user')->withTimestamps();
    }

        public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_user')->withTimestamps();
    }

    public function likedComments()
    {
        return $this->belongsToMany(Comment::class, 'comment_user')->withTimestamps();
    }

    public function bookmarkedPosts()
    {
        return $this->belongsToMany(Post::class, 'bookmark_user')->withTimestamps();
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}