<?php
namespace Jalno\Userpanel\API;

use Jalno\Userpanel\Models\Log;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Logs extends API
{   
    /**
     * @param FilterParameters $filters
     */
    public function search(array $filters = [], ?int $perPage = null, array $columns = ['*'], string $cursorName = 'cursor', ?Cursor $cursor = null): CursorPaginator
    {
        $this->requireAbility("userpanel_logs_search");

        $user = $this->user();
        $query = Log::query();
        $query->with(["user" => function ($query) use(&$user) {
            $types = $user->childrenTypes();
            if ($types) {
                $query->whereIn('usertype_id', $types);
            } else {
                $query->where("id", $user->id);
            }
        }]);

        $this->applyFiltersOnQuery($query, $filters);
        return $query->orderBy('id')->cursorPaginate($perPage);;
    }

    /**
     * @param int|FilterParameter $parameters
     */
    public function find($filters): ?Log
    {
        $this->requireAnyAbility(["userpanel_logs_search", "userpanel_logs_delete", "userpanel_logs_view"]);

        $user = $this->user();
        $query = Log::query();
        $query->with(["user" => function ($query) use(&$user) {
            $types = $user->childrenTypes();
            if ($types) {
                $query->whereIn('usertype_id', $types);
            } else {
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
     * @param FilterParameter|int $parameters
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
