<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use \Illuminate\Database\Query\Builder;

/**
 * @package App
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder from()
 * @method static Builder find($id)
 *
 * @property Tagged $tagged
 * @property User $user
 */
class TaggedUpvote extends Model
{
    protected $guarded = [];
    protected $table = 'tagging_tagged_upvotes';

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function tagged()
    {
        return $this->belongsTo('App\Tagged');
    }

    public function toArray()
    {
        // Nothing for now
        $data = parent::toArray();
        $data['author_firstname'] = $this->user->getFirstName();
        $data['author_avatar'] = $this->user->avatar_path;
        $data['tag_name'] = $this->tagged->tag_name;

        return $data;
    }

    public static function findByTaggedIdAndUserId($taggedId, $userId)
    {
        return self::where('tagged_id', $taggedId)
            ->where('user_id', $userId)
            ->first();
    }
}