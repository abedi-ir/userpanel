<?php
namespace Jalno\Userpanel\API;

use Illuminate\Pagination\Cursor;
use Jalno\Userpanel\Models\{User, Log};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\CursorPaginator as CursorPaginatorContract;

class Logs extends API
{   
    /**
     * @param array<string,mixed> $filters
     * @param string[] $columns
     */
    public function search(array $filters = [], ?int $perPage = null, array $columns = ['*'], string $cursorName = 'cursor', ?Cursor $cursor = null): CursorPaginatorContract
    {
        $this->requireAbility("userpanel_logs_search");

        $user = $this->user();
        $query = Log::query();
        $query->with(["user" => function ($query) use(&$user) {
            $types = (!is_null($user) and method_exists($user, "childrenTypes")) ? $user->childrenTypes() : [];
            if ($types) {
                $query->whereIn('usertype_id', $types);
            } elseif (!is_null($user)) {
                $query->where("id", $user->id);
            }
        }]);

        $this->applyFiltersOnQuery($query, $filters);
        return $query->orderBy('id')->cursorPaginate($perPage);;
    }

    /**
     * @param int|array<string,mixed> $filters
     */
    public function find($filters): ?Log
    {
        $this->requireAnyAbility(["userpanel_logs_search", "userpanel_logs_delete", "userpanel_logs_view"]);

        $user = $this->user();
        $query = Log::query();
        
        $query->with(["user" => function ($query) use(&$user) {
            $types = (!is_null($user) and method_exists($user, "childrenTypes")) ?  $user->childrenTypes() : [];
            if ($types) {
                $query->whereIn('usertype_id', $types);
            } elseif (!is_null($user)) {
                $query->where("id", $user->id);
            }
        }]);

        if (is_numeric($filters)) {
            return $query->find($filters);
        }

        $this->applyFiltersOnQuery($query, $filters);
        return $query->first();
    }

    /**
     * @param array<string,mixed>|int $parameters
     */
    public function delete($parameters): void
    {
        $this->requireAbility("userpanel_logs_delete");

        if (is_numeric($parameters)) {
            $parameters = ["id" => ["in" => [$parameters]]];
        }

        $paginator = null;
        do {
            $paginator = $this->search($parameters, 100, ['*'], 'cursor', $paginator ? $paginator->nextCursor() : null);

            if ($paginator->count() < 1) {
                throw (new ModelNotFoundException)->setModel(Log::class);
            }
    
            foreach ($paginator as $tiem) {
                $tiem->delete();
            }
        } while($paginator->hasMorePages());
    }
}
