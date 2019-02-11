<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @package App
 * @method static \Illuminate\Database\Query\Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static \Illuminate\Database\Query\Builder withAllTags($array)
 * @method static SavedSearch find($id)
 * @method static SavedSearch create($params)
 *
 * @property      $name
 * @property      $query
 * @property      $icon
 * @property      $slug
 * @property      $featured_order
 * @property User $user
 * @property      $updated_at
 */
class SavedSearch extends Model
{

    protected $fillable = [
        'user_id',
        'name',
        'query',
    ];

    public function getSlugOrId()
    {
        return $this->slug ? $this->slug : $this->id;
    }

    public static function findBySlug($slug)
    {
        return self::where('slug', $slug)->first();
    }

    public static function findBySlugOrId($slugOrId)
    {
        $search = SavedSearch::findBySlug($slugOrId);
        if (!$search) {
            $search = SavedSearch::find($slugOrId);
        }

        return $search;
    }

    public function user()
    {
        return $this->hasOne('App\User');
    }

    public function toArrayWithUsers()
    {
        $data = parent::toArray();
        $data['users'] = $this->fetchUsers();

        return $data;
    }

    public function fetchUsers()
    {
        $users = User::with('tags');

        $users->leftJoin("tagging_tagged_upvotes as upvotes", function ($join) {
            /** @var $join \Illuminate\Database\Query\JoinClause */
            $join->on("upvotes.tagged_user_id", '=', 'users.id');
        })->leftJoin("tagging_tagged as tagged", function ($join) {
            /** @var $join \Illuminate\Database\Query\JoinClause */
            $join->on("tagged.taggable_id", '=', 'users.id');
        })->select([
            'users.*',
        ])->groupBy('users.id');

        if ($this->query) {
            $search = new UserSearch($users);
            $search->query($this->query);
        }

        return $users->get();
    }
}
