<?php
namespace Jalno\Userpanel\API;

use Jalno\API\API;
use Jalno\Userpanel\Models\Log;
use Illuminate\Validation\Rule;
use Jalno\Userpanel\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Auth\Access\AuthorizationException;


class Login extends API
{

    /**
     * Register new user
     * @param array<string,string> $parameters
     */
    public function register(array $parameters): User
    {
        $this->requireAbility("userpanel_users_register", true);

        if (!config("jalno.userpanel.register.enable") or !config("jalno.userpanel.register.usertype")) {
            throw new AuthorizationException();
        }

        $parameters = Validator::validate($parameters, array(
            'username' => ['required', 'string', 'max:255', Rule::unique(User\UserName::class, "username")],
            'password' => ['required', 'string', 'max:255', $this->getPasswordValidation()],
        ));

        $parameters["usertype_id"] = config("jalno.userpanel.register.usertype");
        $parameters["status"] = config("jalno.userpanel.register.status", User::ACTIVE);

        $user = new User();
        $user->fill($parameters);
        $user->saveOrFail();

        $username = new User\UserName();
        $username->fill(["user_id" => $user->id, "username" => $parameters["username"]]);
        $username->saveOrFail();

        $log = new Log();
        $log->user_id = $user->id;
        $log->type = "jalno.userpanel.users.logs.register";
        $log->parameters = array(
            "new" => $parameters,
        );
        $log->save();

        return $user;
    }
}
