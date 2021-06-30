<?php

namespace Jalno\Userpanel\Observers;

use Laravel\Passport\Token;
use Jalno\Userpanel\Models\User;
use Illuminate\Support\Facades\Date;

class TokenObserver
{
    /**
     * Handle the Token "created" event.
     *
     * @return void
     */
    public function created(Token $token)
    {
		$user = $this->getUser($token);

		$user->lastlogin_at = Date::now();
		$user->save();
    }

    /**
     * Handle the Token "updated" event.
     *
     * @return void
     */
    public function updated(Token $token)
    {
		$user = $this->getUser($token);

		$user->lastlogin_at = Date::now();
		$user->save();
    }
	
	protected function getUser(Token $token): User
	{
		return $token->user()->getResults();
	}
}
