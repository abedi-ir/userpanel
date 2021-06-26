<?php
namespace Jalno\Userpanel\API;

use Exception;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\Eloquent\{Model, Builder};
use Illuminate\Contracts\Auth\{Authenticatable, Access\Authorizable};
use Illuminate\Auth\{AuthenticationException, Access\AuthorizationException};


/**
 * @phpstan-type Premitive string|int|float
 * @phpstan-type FilterOperationArray array {
 *  "eq"?: Premitive,
 *  "lt"?: Premitive,
 *  "gt"?: Premitive,
 *  "lte"?: Premitive,
 *  "gte"?: Premitive,
 *  "in"?: Premitive[],
 *  "nin"?: Premitive[],
 *  "and"?: FilterParameter,
 *  "or":?: FilterParameter
 * }
 * @phpstan-type FilterParameter array {
 *  string: FilterOperationArray|Premitive
 * }
 */

abstract class API
{

    protected ?Authenticatable $user = null;
    protected ?Password $passwordValidation = null;

    public function forUser(?Authenticatable $user): void {
        $this->user = $user;
    }

    public function user(): ?Authenticatable {
        return $this->user;
    }

    public function applyFiltersOnModel(Model $model, array $filters): void {
        $this->applyFiltersOnQuery($model->newQuery(), $filters);
    }

    /**
     * @param FilterParameter $filters
     */
    public function applyFiltersOnQuery(Builder $query, array $filters): void {
        foreach ($filters as $field => $operators) {
            if (is_string($operators)) {
                $operators = array('eq' => $operators);
            }
            if (!is_array($operators)) {
                throw new Exception("operators is not array: " . var_export($operators, true));
            }
            foreach ($operators as $operator => $value) {
                switch ($operator) {
                    case "eq": $operator = '='; break;
                    case "neq": $operator = '!='; break;
                    case "lt": $operator = '<'; break;
                    case "lte": $operator = '<='; break;
                    case "gt": $operator = '>'; break;
                    case "gte": $operator = '>='; break;
                }
                switch ($operator) {
                    case "=":
                    case "!=":
                    case "<":
                    case "<=":
                    case ">":
                    case ">=":
                        $this->insurePremitiveValue($value);
                        $query->where($field, $operator, $value);
                        break;
                    case "in":
                        $this->insureArrayOfPremitiveValue($value);
                        $query->whereIn($field, $value);
                        break;
                    case "nin":
                        $this->insureArrayOfPremitiveValue($value);
                        $query->whereNotIn($field, $value);
                        break;
                    case "and":
                    case "or":
                        $this->insureLogicalArray($value);
                        $query->where(function(Builder $nested) use ($value) {
                            $this->applyFiltersOnQuery($nested, $value);
                        }, null, null, $operator);
                        break;
                    default:
                        throw new Exception("wrong operator: {$operator}");
                }
            }
        }
    }


    public function requireUser(): void {
        if (!$this->user) {
            throw new AuthenticationException();
        }
    }

    /**
     * @param string[] $abilities
     */
    public function requireAnyAbility(array $abilities, $allowGuest = false): void {
        if (!$this->user) {
            if ($allowGuest) {
                return;
            }
            throw new AuthenticationException();
        }
        if (!$this->user instanceof Authorizable or !$this->user->canAny($abilities)) {
            throw new AuthorizationException();
        }
    }

    /**
     * @param string[] $abilities
     */
    public function requireAbility(string $ability, $allowGuest = false): void {
        if (!$this->user) {
            if ($allowGuest) {
                return;
            }
            throw new AuthenticationException();
        }
        if (!$this->user instanceof Authorizable or !$this->user->can($ability)) {
            throw new AuthorizationException();
        }
    }

    protected function insurePremitiveValue($value): void {
        if (!is_numeric($value) and !is_string($value)) {
            throw new Exception("value must be premitive");
        }
    }
    protected function insureArrayOfPremitiveValue($value): void {
        if (!is_array($value)) {
            throw new Exception("value must be array of premitive");
        }
        foreach ($value as $index) {
            $this->insurePremitiveValue($index);
        }
    }
    protected function insureLogicalArray($value): void {
        if (!is_array($value)) {
            throw new Exception("value must be array");
        }
    }

    protected function getPasswordValidation(): ?Password
    {
        if (is_null($this->passwordValidation) and app('config')->has("jalno.userpanel.users.validation.password")) {
            $min = config("jalno.userpanel.users.validation.password.min", 1);
            $this->passwordValidation = new Password($min);

            if (app('config')->has("jalno.userpanel.users.validation.password.required.letters")) {
                $this->passwordValidation->letters();
            }
            if (app('config')->has("jalno.userpanel.users.validation.password.required.mixedCase")) {
                $this->passwordValidation->mixedCase();
            }
            if (app('config')->has("jalno.userpanel.users.validation.password.required.numbers")) {
                $this->passwordValidation->numbers();
            }
            if (app('config')->has("jalno.userpanel.users.validation.password.required.symbols")) {
                $this->passwordValidation->symbols();
            }
        }

        return $this->passwordValidation;
    }
}
