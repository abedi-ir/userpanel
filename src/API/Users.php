<?php
namespace Jalno\Userpanel\API;

use Illuminate\Validation\Rule;
use Jalno\Userpanel\Models\Log;
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
        $logParameters = [
            "old" => [],
        ];
        do {
            $paginator = $this->search($parameters, 100, ['*'], 'cursor', $paginator ? $paginator->nextCursor() : null);

            if ($paginator->count() < 1) {
                throw (new ModelNotFoundException)->setModel(User::class);
            }
    
            foreach ($paginator as $item) {
                $logParameter = $item->toArray();
                $logParameter["usernames"] = $item->usernames->pluck("username");
                if ($item->has_custom_permissions) {
                    $logParameter["permissions"] = $item->permissions->pluck("name");
                }
                $logParameters["old"][] = $logParameter;
                $item->delete();
            }

        } while($paginator->hasMorePages());

        if (!empty($logParameters["old"])) {
            $log = new Log();
            $log->user_id = $this->user()->id;
            $log->type = "jalno.userpanel.users.logs.delete";
            $log->parameters = $logParameters;
            $log->save();
        }
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

        $logParameters= [
            "new" => [],
            "old" => [],
        ];
        foreach ($parameters as $key => $value) {
            if (in_array($key, ["username", "permissions"])) {
                continue;
            }
            $isSensitive = in_array($key, ["password"]);
            if ($user->id) {
                if ($user->{$key} != $value) {
                    $logParameters["new"][$key] = $isSensitive ? "***" : $value;
                    $logParameters["old"][$key] = $isSensitive ? "***" : $user->{$key};
                }
            } else {
                $logParameters["new"][$key] = $isSensitive ? "***" : $value;
            }
            $user->{$key} = $value;
        }
        $user->saveOrFail();

        if (isset($parameters["username"])) {
            $usernames = $user->names;

            if ($usernames) {
                $deletedUserNames = $usernames->filter(fn($username) => !in_array($username->username, $parameters["username"]));
                if (!empty($deletedUserNames)) {
                    $logParameters["old"]["usernames"] = [];
                    foreach ($deletedUserNames as $username) {
                        $logParameters["old"]["usernames"][] = $username->username;
                        $username->delete();
                    }
                }

                $parameters["username"] = array_diff($parameters["username"], $usernames->diff($deletedUserNames)->pluck("username")->all());
            }

            if (!empty($parameters["username"])) {
                $logParameters["new"]["usernames"] = [];

                foreach ($parameters["username"] as $username) {
                    $model = new User\UserName();
                    $model->user_id = $user->id;
                    $model->username = $username;
                    $model->saveOrFail();
    
                    $logParameters["new"]["usernames"][] = $username;
                }
            }
        }

        if ($hasCustomePermission and !$user->has_custom_permissions) {
            $permisssions = $user->permissions;

            if ($permisssions) {
                $logParameters["old"]["permissions"] = [];
                foreach ($permissions as $permission) {
                    $logParameters["old"]["permissions"][] = $permission->name;
                    $permission->delete();
                }
            }
        } elseif (isset($parameters["permissions"])) {
            $permissions = $user->permissions;
            if ($permissions) {
                $deletedPermissions = $permissions->filter(fn($item) => !in_array($item->name, $parameters["permissions"]));
                
                if ($deletedPermissions) {
                    $logParameters["old"]["permissions"] = [];
                    foreach ($deletedPermissions as $permission) {
                        $logParameters["old"]["permissions"][] = $permission->name;
                        $permission->delete();
                    }
                }

                $parameters["permissions"] = array_diff($parameters["permissions"], $permissions->diff($deletedPermissions)->pluck("name")->all());
            }
            
            if (!empty($parameters["permissions"])) {
                $logParameters["new"]["permissions"] = [];
                foreach ($parameters["permissions"] as $name) {
                    $model = new User\Permission();
                    $model->user_id = $user->id;
                    $model->name = $name;
                    $model->saveOrFail();

                    $logParameters["new"]["permissions"][] = $name;
                }
            }
        }

        $isEditing = !empty($logParameters["old"]);

        if ($this->user()->id != $user->id) {
            $log = new Log();
            $log->user_id = $this->user()->id;
            $log->type = "jalno.userpanel.users.logs." . ($isEditing ? "edit" : "add");
            $log->parameters = $logParameters;
            $log->save();
        }

        $log = new Log();
        $log->user_id = $user->id;
        $log->type = "jalno.userpanel.users.logs." . ($isEditing ? "update" : "register");
        $log->parameters = $logParameters;
        $log->save();

        return $user;
    }
}
