<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
// use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UniqueAcrossTables  implements ValidationRule
{
     protected $tables = ['system_admins', 'solar_company_managers', 'agency_managers', 'customers', 'employees','solar_companies','agencies'];
    protected $column;
    protected $ignoreId;
    protected $ignoreTable;
     public function __construct($column, $ignoreId = null, $ignoreTable = null)
    {
        $this->column = $column;
        $this->ignoreId = $ignoreId;
        $this->ignoreTable = $ignoreTable;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $currentUserId = $this->ignoreId ;
        $currentTable = $this->ignoreTable ;

          foreach ($this->tables as $table) {
            if($table === 'solar_companies' &&( $this->column === 'company_email'|| $this->column === 'email')){
                $column = 'company_email';
            }elseif($table === 'solar_companies' &&( $this->column === 'company_phone'|| $this->column === 'phoneNumber')){
                $column = 'company_phone';
            }elseif($table === 'agencies' &&( $this->column === 'agency_email'|| $this->column === 'email')){
                $column = 'agency_email';
            }elseif($table === 'agencies' &&( $this->column === 'agency_phone'|| $this->column === 'phoneNumber')){
                $column = 'agency_phone';
            }else{
                $column = $this->column;
            }
            $query = DB::table($table)->where($column, $value);

        if ($currentUserId && $currentTable === $table) {
            $query->where('id', '!=', $currentUserId);
        }

            if ($query->exists()) {
                $fail("value '{$value}' use already");
                return;
            }
        }
    }

    /**
     * Format phone number to international format
     */

    }

