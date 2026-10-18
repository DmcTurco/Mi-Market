<?php

namespace App\Http\Responses;

use App\Constants;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract

{

    /**

     * @param  $request

     * @return mixed

     */

    public function toResponse($request)

    {

        return redirect(config('fortify.login') ? config('fortify.login') : route('login'));

    }

}
