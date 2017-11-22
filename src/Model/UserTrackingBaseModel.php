<?php

namespace InfyOm\Generator\Model;

use Eloquent as Model;
use Auth;
use App\User;

/**
 * Class Command
 * @package App\Models
 * @version September 6, 2016, 6:54 am UTC
 */
class UserTrackingBaseModel extends Model
{
    /**
     * custom constructor setting user_id from current user
     */
    public function __construct(array $attributes = array())
    {
        $user = Auth::user();
        if ($user)
            $user_id = $user->id;
        else
            $user_id = User::orderBy('id')->first()->id;

        $this->setRawAttributes(array(
            'user_id' => $user_id
        ), true);

        $attributes['user_id'] = $user_id;
        parent::__construct($attributes);
    }
}
