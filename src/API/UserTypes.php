<?php
namespace Jalno\Userpanel\API;

use Jalno\Userpanel\Exceptions;
use Illuminate\Validation\Rule;
use Jalno\Userpanel\Models\UserType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UserTypes extends API
{   
    /**
     * @param FilterParameters $filters
     */
    public function search(array $filters = [], ?int $perPage = null, array $columns = ['*'], string $cursorName = 'cursor', ?Cursor $cursor = null): CursorPaginator
    {
        $this->requireAbility("userpanel_usertypes_search");
        $query = UserType::query();
        $types = $this->user()->childrenTypes();
        $query->whereIn("id", $types);

        $this->applyFiltersOnQuery($query, $filters);
        return $query->orderBy('id')->cursorPaginate($perPage);;
    }

    /**
     * @param int|FilterParameter $parameters
     */
    public function find($filters): ?UserType
    {
        $this->requireAnyAbility(["userpanel_usertypes_search", "userpanel_usertypes_edit", "userpanel_usertypes_delete", "userpanel_usertypes_view"]);

        $query = UserType::query();

        $types = $this->user()->childrenTypes();

        if (!$types) {
            throw new NotFoundException();//TODO
        }

        $query->whereIn("id", $types);

        if (is_numeric($filters)) {
            return $query->find($filters);
        }

        $this->applyFiltersOnQuery($query, $filters);
        return $query->first();
    }

    /**
     * @param array{"names":string[],"permissions":string[],"priorities":int[]} $parameters
     */
    public function add(array $parameters): UserType
    {
        $this->requireAbility("userpanel_usertypes_add");

        $types = $this->user()->childrenTypes();
        if (!$types) {
            throw new Exceptions\NotAllowedException();
        }

        return $this->createOrUpdate($parameters);
    }

    /**
     * @param array{"names"?:string[],"permissions"?:string[],"priorities"?:string[]} $parameters
     */
    public function edit(array $parameters): UserType
    {
        $this->requireAbility("userpanel_usertypes_edit");

        $usertype = $this->find($parameters['usertype']);
        if (!$usertype) {
            throw (new ModelNotFoundException)->setModel(UserType::class);
        }
        unset($parameters['usertype']);

        return $this->createOrUpdate($parameters, $usertype);
    }

    /**
     * @param FilterParameter|int $parameters
     */
    public function delete($parameters): void
    {
        $this->requireAbility("userpanel_usertypes_delete");

        if (is_numeric($parameters)) {
            $parameters = ["id" => ["in" => [$parameters]]];
        }

        $paginator = null;
        do {
            $paginator = $this->search($parameters, 100, ['*'], 'cursor', $paginator ? $paginator->nextCursor() : null);

            if ($paginator->count() < 1) {
                throw (new ModelNotFoundException)->setModel(UserType::class);
            }
    
            foreach ($paginator as $tiem) {
                $tiem->delete();
            }
        } while($paginator->hasMorePages());
    }

    /**
     * @param array{"names"?:string[],"permissions"?:string[],"priorities"?:string[]} $parameters
     */
    protected function createOrUpdate(array $parameters, ?UserType $usertype = null): UserType
    {
        $required = ($usertype ? 'sometimes' : 'required');
        $parameters = Validator::validate($parameters, array(
            'names' => [$required, 'array', 'min:1'],
            'names.*' => ['string', 'min:3', 'max:255'],
            'permissions' => [$required, 'array', 'min:1'],
            'permissions.*' => ['string', 'max:255'],
            'priorities' => ['sometimes', 'array'],
            'priorities.*' => ['numeric', Rule::in($this->user()->childrenTypes())],
        ));

        if (isset($parameters["permissions"])) {
            foreach ($parameters["permissions"] as $key => $permission) {
                if (!$this->user()->can($permission)) {
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

        if (!$usertype) {
            $usertype = UserType::create();

            $parentTypes = $this->user()->parentTypes();

            if (!in_array($this->user()->usertype_id, $parentTypes)) {
                $parentTypes[] = $this->user()->usertype_id;
            }

            foreach ($parentTypes as $parent) {
                \DB::table("userpanel_usertypes_priorities")->insert([
                    "parent_id" => $parent,
                    "child_id" => $usertype->id,
                ]);
            }
        }

        if (isset($parameters["names"])) {
            $usertypeNames = (new UserType\Name)->where("usertype_id", $usertype->id)->get();

            if ($usertypeNames) {
                $deletedNames = $usertypeNames->filter(fn($name) => !isset($parameters["names"][$name->lang]) or !in_array($name->name, $parameters["names"]));
                foreach ($deletedNames as $username) {
                    $username->delete();
                }

                $parameters["names"] = array_diff($parameters["names"], $usertypeNames->diff($deletedNames)->pluck("name")->all());
            }

            foreach ($parameters["names"] as $lang => $name) {
                $model = new UserType\Name();
                $model->usertype_id = $usertype->id;
                $model->lang = $lang;
                $model->name = $name;
                $model->saveOrFail();
            }
        }

        if (isset($parameters["permissions"])) {
            $permissions = $usertype->permissions;

            if ($permissions) {
                $deletedPermissions = $permissions->filter(fn($item) => !in_array($item->name, $parameters["permissions"]));
                foreach ($deletedPermissions as $permission) {
                    $permission->delete();
                }

                $parameters["permissions"] = array_diff($parameters["permissions"], $permissions->diff($deletedPermissions)->pluck("name")->all());
            }

            foreach ($parameters["permissions"] as $name) {
                $model = new UserType\Permission();
                $model->usertype_id = $usertype->id;
                $model->name = $name;
                $model->saveOrFail();
            }
        }

        return $usertype;
    }
}
