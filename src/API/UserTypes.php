<?php
namespace Jalno\Userpanel\API;

use Jalno\API\API;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\Cursor;
use Jalno\Userpanel\Models\UserType;
use Jalno\Userpanel\Models\{Log, User};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserTypes extends API
{   
    /**
     * @param array<string,mixed> $filters
     * @param string[] $columns
     */
    public function search(array $filters = [], ?int $perPage = null, array $columns = ['*'], string $cursorName = 'cursor', ?Cursor $cursor = null): CursorPaginator
    {
        $this->requireAbility("userpanel_usertypes_search");
        $query = UserType::query();

        $user = $this->user();
        $types = (!is_null($user) and method_exists($user, "childrenTypes")) ? $user->childrenTypes() : [];

        $query->whereIn("id", $types);

        $this->applyFiltersOnQuery($query, $filters);
        return $query->orderBy('id')->cursorPaginate($perPage);;
    }

    /**
     * @param int|array<string,mixed> $filters
     */
    public function find($filters): ?UserType
    {
        $this->requireAnyAbility(["userpanel_usertypes_search", "userpanel_usertypes_edit", "userpanel_usertypes_delete", "userpanel_usertypes_view"]);

        $query = UserType::query();
        $user = $this->user();
        $types = (!is_null($user) and method_exists($user, "childrenTypes")) ? $user->childrenTypes() : [];

        if (!$types) {
            throw new AuthorizationException();
        }

        $query->whereIn("id", $types);

        if (is_numeric($filters)) {
            return $query->find($filters);
        }

        $this->applyFiltersOnQuery($query, $filters);
        return $query->first();
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function add(array $parameters): UserType
    {
        $this->requireAbility("userpanel_usertypes_add");

        $user = $this->user();
        $types = (!is_null($user) and method_exists($user, "childrenTypes")) ? $user->childrenTypes() : [];
        if (!$types) {
            throw new AuthorizationException();
        }

        return $this->createOrUpdate($parameters);
    }

    /**
     * @param array<string,mixed> $parameters
     */
    public function edit(int $id, array $parameters): UserType
    {
        $this->requireAbility("userpanel_usertypes_edit");

        $usertype = $this->find($id);
        if (!$usertype) {
            throw (new ModelNotFoundException)->setModel(UserType::class);
        }

        return $this->createOrUpdate($parameters, $usertype);
    }

    /**
     * @param int|array<string,mixed> $parameters
     */
    public function delete($parameters): void
    {
        $this->requireAbility("userpanel_usertypes_delete");

        if (is_numeric($parameters)) {
            $parameters = ["id" => ["in" => [$parameters]]];
        }

        $paginator = null;
        $logParameters = [
            "old" => [],
        ];
        do {
            $paginator = $this->search($parameters, 100, ['*'], 'cursor', $paginator ? $paginator->nextCursor() : null);

            if ($paginator->count() < 1) {
                throw (new ModelNotFoundException)->setModel(UserType::class);
            }
    
            foreach ($paginator as $item) {
                $hasUser = User::where("usertype_id", $item->id)->exists();
                if ($hasUser) {
                    throw ValidationException::withMessages(["usertypes.{$item->id}" => "The selected usertype has user."]);
                }
                $logParameters["old"][] = $item->toArray();
                $item->delete();
            }
        } while($paginator->hasMorePages());

        if (!empty($logParameters["old"]) and !is_null($this->user())) {
            $log = new Log();
            $log->user_id = $this->user()->id;
            $log->type = "jalno.userpanel.usertypes.logs.delete";
            $log->parameters = $logParameters;
            $log->save();
        }
    }

    /**
     * @param array<string,mixed> $parameters
     */
    protected function createOrUpdate(array $parameters, ?UserType $usertype = null): UserType
    {
        $required = ($usertype ? 'sometimes' : 'required');
        $user = $this->user();
        $parameters = Validator::validate($parameters, array(
            'names' => [$required, 'array', 'min:1'],
            'names.*' => ['string', 'min:3', 'max:255'],
            'permissions' => [$required, 'array', 'min:1'],
            'permissions.*' => ['string', 'max:255'],
            'priorities' => ['sometimes', 'array'],
            'priorities.*' => ['numeric', Rule::in((!is_null($user) and method_exists($user, "childrenTypes")) ? $user->childrenTypes() : [])],
        ));

        if (isset($parameters["permissions"]) and !is_null($user)) {
            foreach ($parameters["permissions"] as $key => $permission) {
                if (!$user->can($permission)) {
                    throw ValidationException::withMessages(["permissions.{$key}" => "The selected permission {$key} is invalid."]);
                }
            }
        }

        if (isset($parameters["names"])) {
            foreach ($parameters["names"] as $lang => $name) {
                if (is_numeric($lang) or strlen($lang) > 2) {
                    throw ValidationException::withMessages(["names.{$lang}" => "The selected lang is invalid."]);
                }
            }
        }

        $logParameters = [
            "old" => [],
            "new" => [],
        ];

        if (!$usertype) {
            $usertype = UserType::create();

            $parentTypes = (!is_null($user) and method_exists($user, "parentTypes")) ? $user->parentTypes() : [];

            if ($user instanceof User and !in_array($user->usertype_id, $parentTypes)) {
                $parentTypes[] = $user->usertype_id;
            }

            $logParameters["new"]["parentTypes"] = $parentTypes;

            foreach ($parentTypes as $parent) {
                \DB::table("userpanel_usertypes_priorities")->insert([
                    "parent_id" => $parent,
                    "child_id" => $usertype->id,
                ]);
            }
        }

        if (isset($parameters["priorities"])) {
            $priorities = $usertype->childrenTypes();

            if ($priorities) {
                $deletedPriorities = array_diff($priorities, $parameters["priorities"]);

                if ($deletedPriorities) {
                    $logParameters["old"]["priorities"] = $deletedPriorities;
                    \DB::table("userpanel_usertypes_priorities")
                        ->where("parent_id", $usertype->id)
                        ->whereIn("child_id", $deletedPriorities)
                        ->delete();
                }

                $parameters["priorities"] = array_diff($parameters["priorities"], array_diff($priorities, $deletedPriorities));
                
            }

            if (!empty($parameters["priorities"])) {
                $logParameters["new"]["priorities"] = $parameters["priorities"];

                foreach ($parameters["priorities"] as $priority) {
                    \DB::table("userpanel_usertypes_priorities")->insert([
                        "parent_id" => $usertype->id,
                        "child_id" => $priority,
                    ]);
                }
            }
        }

        if (isset($parameters["names"])) {
            $usertypeNames = $usertype->names;

            if ($usertypeNames) {
                $deletedNames = $usertypeNames->filter(fn($name) => !isset($parameters["names"][$name->lang]) or !in_array($name->text, $parameters["names"]));
                
                if ($deletedNames->isNotEmpty()) {
                    $logParameters["old"]["names"] = [];
                    foreach ($deletedNames as $username) {
                        $logParameters["old"]["names"][$username->lang] = $username->text;
                        $username->delete();
                    }
                }

                $parameters["names"] = array_diff($parameters["names"], $usertypeNames->diff($deletedNames)->pluck("name")->all());
            }

            if ($parameters["names"]) {
                $logParameters["new"]["names"] = $parameters["names"];
                foreach ($parameters["names"] as $lang => $name) {
                    $usertype->addTitle($lang, $name);
                }
            }
        }

        if (isset($parameters["permissions"])) {
            $permissions = $usertype->permissions;

            if ($permissions) {
                $deletedPermissions = $permissions->filter(fn($item) => !in_array($item->name, $parameters["permissions"]));
                
                if ($deletedPermissions->isNotEmpty()) {
                    $logParameters["old"]["names"] = [];

                    foreach ($deletedPermissions as $permission) {
                        $logParameters["old"]["names"][] = $permission->name;
                        $permission->delete();
                    }
                }

                $parameters["permissions"] = array_diff($parameters["permissions"], $permissions->diff($deletedPermissions)->pluck("name")->all());
            }

            if ($parameters["permissions"]) {
                $logParameters["new"]["names"] = [];

                foreach ($parameters["permissions"] as $name) {
                    $model = new UserType\Permission();
                    $model->usertype_id = $usertype->id;
                    $model->name = $name;
                    $model->saveOrFail();

                    $logParameters["new"]["names"][] = $name;
                }
            }
        }

        if (!is_null($user)) {
            $log = new Log();
            $log->user_id = $user->id;
            $log->type = "jalno.userpanel.usertypes.logs." . (empty($logParameters["old"]) ? "add" : "update");
            $log->parameters = $logParameters;
            $log->save();
        }

        return $usertype;
    }
}
