<?php

namespace App;

use DB;
use OE\Lukas\Parser\QueryParser;
use OE\Lukas\QueryTree\ConjunctiveExpressionList;
use OE\Lukas\QueryTree\DisjunctiveExpressionList;
use OE\Lukas\QueryTree\ExplicitTerm;
use OE\Lukas\QueryTree\Negation;
use OE\Lukas\QueryTree\SubExpression;
use OE\Lukas\QueryTree\Text;
use OE\Lukas\QueryTree\Word;

/**
 * @package App
 */
class UserSearch extends ModelSearch
{

    /** @var \Illuminate\Database\Query\Builder */
    protected $users;
    protected $alias = 'users';

    protected $searchFilters = [
        'created-after',
        'limit',
        'has-tag',
        'online-after',
        'order-by',
        'order-direction',
        'props-from',
        'upvoted-by',
        'tag',
    ];

    public function __construct($users)
    {
        $this->users = $users;
    }

    /**
     * @param $query
     */
    public function query($query)
    {
        $parser = new QueryParser();
        $parser->readString($query);
        $parsedQuery = $parser->parse();

        $this->_query($this->users, $parsedQuery);
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $parsedQuery
     */
    protected function _query($collection, $parsedQuery)
    {
        if ($parsedQuery instanceof Word) {
            $this->_queryWord($collection, $parsedQuery->getToken());
            return;
        } elseif ($parsedQuery instanceof Text) {
            $this->_queryWord($collection, $parsedQuery->getToken());
            return;
        } elseif ($parsedQuery instanceof ExplicitTerm) {
            $this->_callSearchFilterFunction($collection, $parsedQuery);
            return;
        } elseif ($parsedQuery instanceof ConjunctiveExpressionList) {
            foreach ($parsedQuery->getExpressions() as $expression) {
                $this->_query($collection, $expression);
            }
        } elseif ($parsedQuery instanceof DisjunctiveExpressionList) {
            $collection->whereNested(function($model) use($parsedQuery) {
                foreach ($parsedQuery->getExpressions() as $expression) {
                    /** @var $model \Illuminate\Database\Query\Builder */
                    $model->orWhere(function($innerModel) use ($expression) {
                        $this->_query($innerModel, $expression);
                    });
                }
            });
        } elseif ($parsedQuery instanceof Negation) {
            $collection->where(function($innerModel) use ($parsedQuery) {
                $this->_query($innerModel, $parsedQuery->getSubExpression());
            }, null, null, 'AND NOT');
        } elseif ($parsedQuery instanceof SubExpression) {
            $this->_query($collection, $parsedQuery->getSubExpression());
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryWord($collection, $word)
    {
        $collection->whereNested(function ($model) use ($word) {
            /** @var $model \Illuminate\Database\Query\Builder */
            $model->where('users.name', 'like', "%$word%")
                ->orWhere('users.email', 'like', "%$word%")
                ->orWhere('users.id', $word);
        });
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryCreatedAfter($collection, $word)
    {
        try {
            $date = Date::parse($word);
            $collection->where('users.created_at', '>', $date);
        } catch (\Exception $e) {
            // Ignore parse exceptions with date
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryOnlineAfter($collection, $word)
    {
        try {
            $date = Date::parse($word);
            $collection->where('users.last_online_at', '>', $date);
        } catch (\Exception $e) {
            // Ignore parse exceptions with date
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryTag($collection, $word)
    {
        $tags = Tagged::where('tagging_tagged.id', '>', 0)
            ->where('tag_slug', $word)
            ->get()->pluck('taggable_id');

        $collection->whereIn('users.id', $tags);
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryHasTag($collection, $word)
    {
        if ($word) {
            $collection->whereNotNull('tagged.taggable_id');
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryUpvotedBy($collection, $word)
    {
        $user = User::findByUsername($word);
        if ($user) {
            $collection->where('upvotes.user_id', $user->id);
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryPropsFrom($collection, $word)
    {
        $user = User::findByUsername($word);
        if ($user) {
            $collection->where('upvotes.user_id', $user->id)
                ->whereNotNull('upvotes.message');
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryLimit($collection, $word)
    {
        if ($word > 0) {
            $collection->limit($word);
        }
    }

    /**
     * @param $collection \Illuminate\Database\Query\Builder
     * @param $word
     */
    protected function _queryOrderBy($collection, $word)
    {
        if (strpos($word, '.')) {
            $parts = explode('.', $word);
            if (isset($parts[0]) && isset($parts[1])) {
                $collection->orderBy("users." . $parts[0], $parts[1]);
            } else {
                $collection->orderBy("users." . $parts[0]);
            }
        } else {
            $collection->orderBy($word);
        }
    }
}