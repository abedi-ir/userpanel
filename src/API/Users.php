<?php
namespace Jalno\Userpanel\API;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Jalno\Userpanel\Models\{User, UserType};
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\{Cursor, CursorPaginator};
use Illuminate\Database\Eloquent\ModelNotFoundException;

class Users extends API
{
    
    public function search(array $parameters = [], ?int $perPage = null, array $columns = ['*'], string $cursorName = 'cursor', ?Cursor $cursor = null): CursorPaginator
    {
        $this->requireAbility("userpanel_users_search");
        $query = User::query();
        $types = $this->user()->childrenTypes();
        if ($types) {
            $query->whereIn("usertype_id", $types);
        } else {
            $query->where("id", $this->user()->id);
        }
        $this->applyFiltersOnQuery($query, $parameters);
        return $query->orderBy('id')->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    /**
     * @param int|array $parameters
     */
    public function find($parameters): ?User
    {
        $this->requireAnyAbility(["userpanel_users_search", "userpanel_users_edit", "userpanel_users_delete", "userpanel_users_view"]);

        $query = User::query();

        $types = $this->user()->childrenTypes();
        if ($types) {
            $query->whereIn("usertype_id", $types);
        } else {
            $query->where("id", $this->user()->id);
        }

        if (is_numeric($parameters)) {
            return $query->find($parameters);
        }

        $this->applyFiltersOnQuery($query, $parameters);
        return $query->first();
    }

    /**
     * @param array $parameters
     */
    public function add(array $parameters): User
    {
        $this->requireAbility("userpanel_users_add");

        $types = $this->user()->childrenTypes();
        if (!$types) {
            throw new AuthorizationException();
        }

        $minUsernameLength = config("jalno.userpanel.users.validation.usernames.min", 1);

        return $this->createOrUpdate($parameters);

    }

    /**
     * @param array $parameters
     */
    public function edit(array $parameters): User
    {
        $this->requireAbility("userpanel_users_edit");

        $types = $this->user()->childrenTypes();
        if (empty($types)) {
            throw new AuthorizationException();
        }

        $user = $this->find($parameters['user']);
        if (!$user) {
            $exception = new ModelNotFoundException();
            $exception->setModel(User::class, $parameters["user"]);
            throw $exception;
        }
        unset($parameters['user']);

        return $this->createOrUpdate($parameters, $user);
    }

    /**
     * @param int[]|int $parameters
     */
    public function delete($parameters): void
    {
        $this->requireAbility("userpanel_users_delete");

        if (is_numeric($parameters)) {
            $parameters = ["id" => ["in" => [$parameters]]];
        }

        $paginator = null;
        do {
            $paginator = $this->search($parameters, 100, ['*'], 'cursor', $paginator ? $paginator->nextCursor() : null);

            if ($paginator->count() < 1) {
                throw (new ModelNotFoundException)->setModel(User::class);
            }
    
            foreach ($paginator as $tiem) {
                $tiem->delete();
            }
        } while($paginator->hasMorePages());
    }

    /**
     * @param array{"username"?:string[],"has_custom_permissions"?:bool,"permissions"?:string[],"password"?:string,"status"?:int} $parameters
     */
    protected function createOrUpdate(array $parameters, ?User $user = null): User
    {
        $required = ($user ? 'sometimes' : 'required');
        $uniqueUserNameRule = Rule::unique(User\UserName::class, "username");
        if ($user) {
            $uniqueUserNameRule->ignore($user->id, "user_id");
        }
        $parameters = Validator::validate($parameters, array(
            'username' => [$required, 'array', 'min:1'],
            'username.*' => ['string', 'max:255', $uniqueUserNameRule],
            'has_custom_permissions' => ['sometimes', 'boolean'],
            'permissions.*' => ['string', 'max:255'],
            'password' => [$required, 'string', 'max:255', $this->getPasswordValidation()],
            'status' => [$required, 'numeric', Rule::in([User::ACTIVE, User::DEACTIVE, User::SUSPEND])],
            'usertype_id' => [$required, 'numeric', Rule::in($this->user()->childrenTypes())],
        ));

        $canChangeCustomePermissions = (($user and $user->has_custom_permissions) or (isset($parameters["has_custom_permissions"]) and $parameters["has_custom_permissions"]));

        if (!$canChangeCustomePermissions) {
            unset($parameters["permissions"]);
        }

        if (isset($parameters["permissions"])) {
            foreach ($parameters["permissions"] as $key => $name) {
                if (!$this->user()->can($name)) {
                    throw ValidationException::withMessages(["permissions.{$key}" => "The selected permission {$key} is invalid."]);
                }
            }
        }

        $hasCustomePermission = ($user and $user->has_custom_permissions);

        if (!$user) {
            $user = new User();
        }

        foreach ($parameters as $key => $value) {
            if (in_array($key, ["username", "permissions"])) {
                continue;
            }
            $user->{$key} = $value;
        }
        $user->saveOrFail();

        if (isset($parameters["username"])) {
            $usernames = $user->names;

            if ($usernames) {
                $deletedUserNames = $usernames->filter(fn($username) => !in_array($username->username, $parameters["username"]));
                foreach ($deletedUserNames as $username) {
                    $username->delete();
                }

                $parameters["username"] = array_diff($parameters["username"], $usernames->diff($deletedUserNames)->pluck("username")->all());
            }

            foreach ($parameters["username"] as $username) {
                $model = new User\UserName();
                $model->user_id = $user->id;
                $model->username = $username;
                $model->saveOrFail();
            }
        }

        if ($hasCustomePermission and !$user->has_custom_permissions) {
            User\Permission::query()->where("user_id", $user->id)->delete();
        } elseif (isset($parameters["permissions"])) {
            $permissions = $user->permissions;
            if ($permissions) {
                $deletedPermissions = $permissions->filter(fn($item) => !in_array($item->name, $parameters["permissions"]));
                foreach ($deletedPermissions as $permission) {
                    $permission->delete();
                }

                $parameters["permissions"] = array_diff($parameters["permissions"], $permissions->diff($deletedPermissions)->pluck("name")->all());
            }

            foreach ($parameters["permissions"] as $name) {
                $model = new User\Permission();
                $model->user_id = $user->id;
                $model->name = $name;
                $model->saveOrFail();
            }
        }

        return $user;
    }
}
